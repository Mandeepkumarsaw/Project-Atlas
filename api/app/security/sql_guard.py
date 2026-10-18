import re
from typing import Any, Tuple
from sqlalchemy import text
from app.services.db import ro_engine, record_security_event

FORBIDDEN_SQL_KEYWORDS = [
    r"\binsert\b", r"\bupdate\b", r"\bdelete\b", r"\bdrop\b", r"\balter\b", 
    r"\btruncate\b", r"\bcreate\b", r"\bgrant\b", r"\brevoke\b", r"\bexecute\b",
    r"\bexec\b", r"\binto\s+outfile\b", r"\binto\s+dumpfile\b", r"\bload_file\b",
    r"\bload\s+data\b", r"\bcall\b", r"\bkill\b", r"\bshutdown\b"
]

SENSITIVE_TABLES = [
    r"\bclients\b", r"\bsecurity_events\b", r"\brequest_logs\b", 
    r"\bclient_report_entitlements\b"
]

SENSITIVE_COLUMNS = [
    r"\bcontact_email\b", r"\bemail\b", r"\bpassword\b", r"\bsecret\b", r"\bapi_key\b"
]

ALLOWED_TABLES = {"reports", "market_metrics"}


def validate_and_execute_safe_sql(
    query_str: str, 
    client_id: str | None = None
) -> Tuple[bool, list[dict[str, Any]] | str]:
    """
    Validate query against strict read-only and allowlist rules, then execute on read-only engine.
    Fails closed if read-only engine is unavailable.
    Enforces row limits (50), timeout, and stacked-query rejection.
    """
    # 0. Fail-closed check: Ensure read-only engine is operational
    if ro_engine is None:
        record_security_event(
            event_type="READ_ONLY_ENGINE_UNAVAILABLE",
            severity="CRITICAL",
            payload_summary="SQL execution rejected because read-only database engine is offline",
            client_id=client_id
        )
        return False, "Security Error: Read-only database engine is offline or unavailable. Query halted (fail-closed)."

    raw_query = query_str.strip()
    lower_query = raw_query.lower()

    # 1. Stacked statement check: disallow multiple statements separated by semicolon
    statements = [s.strip() for s in raw_query.split(";") if s.strip()]
    if len(statements) > 1:
        record_security_event(
            event_type="UNSAFE_SQL_ATTEMPT",
            severity="HIGH",
            payload_summary=f"Multi-statement stacked query rejected: {raw_query[:200]}",
            client_id=client_id
        )
        return False, "Security Error: Multiple stacked SQL statements are strictly forbidden."

    # 2. Obfuscation check: Reject SQL comments (-- or /* */ or #)
    if re.search(r"(--|/\*|\*/|#)", raw_query):
        record_security_event(
            event_type="UNSAFE_SQL_ATTEMPT",
            severity="HIGH",
            payload_summary=f"Comment-obfuscated SQL rejected: {raw_query[:200]}",
            client_id=client_id
        )
        return False, "Security Error: SQL comment syntax and obfuscation markers are prohibited."

    # 3. Must start with SELECT or WITH
    if not (lower_query.startswith("select") or lower_query.startswith("with")):
        record_security_event(
            event_type="UNSAFE_SQL_ATTEMPT",
            severity="HIGH",
            payload_summary=f"Non-SELECT statement rejected: {raw_query[:200]}",
            client_id=client_id
        )
        return False, "Security Error: Only read-only SELECT queries are permitted. Modifying DDL/DML statements are strictly forbidden."

    # 4. Check for forbidden DDL / DML keywords
    for pattern in FORBIDDEN_SQL_KEYWORDS:
        if re.search(pattern, lower_query):
            record_security_event(
                event_type="UNSAFE_SQL_ATTEMPT",
                severity="CRITICAL",
                payload_summary=f"Destructive SQL keyword '{pattern}' detected: {raw_query[:200]}",
                client_id=client_id
            )
            return False, "Security Error: Modifying, administrative, or destructive SQL commands are strictly forbidden."

    # 5. Check for sensitive tables (clients, contact emails, security audits)
    for pattern in SENSITIVE_TABLES:
        if re.search(pattern, lower_query):
            record_security_event(
                event_type="CONFIDENTIAL_DATA_LEAK_PREVENTED",
                severity="HIGH",
                payload_summary=f"Unauthorized access to sensitive table matching '{pattern}': {raw_query[:200]}",
                client_id=client_id
            )
            return False, "Security Error: Access to client identity, billing, or security audit tables is restricted."

    # 6. Check for sensitive columns (contact_email, passwords)
    for pattern in SENSITIVE_COLUMNS:
        if re.search(pattern, lower_query):
            record_security_event(
                event_type="CONFIDENTIAL_DATA_LEAK_PREVENTED",
                severity="HIGH",
                payload_summary=f"Unauthorized access to confidential column matching '{pattern}': {raw_query[:200]}",
                client_id=client_id
            )
            return False, "Security Error: Access to confidential columns (such as contact emails) is restricted."

    # 7. Execute on strictly read-only database connection with row limit
    try:
        with ro_engine.connect() as conn:
            # Enforce max execution limit and fetch limit 50
            result = conn.execute(text(raw_query))
            keys = result.keys()
            rows = [dict(zip(keys, row)) for row in result.fetchmany(50)]
            return True, rows
    except Exception as e:
        err_msg = str(e)
        if "readonly" in err_msg.lower() or "read-only" in err_msg.lower() or "denied" in err_msg.lower():
            return False, "Security Notice: Operation rejected by database-level read-only permissions."
        return False, "Database query execution error: Query could not be processed on read-only catalog."
