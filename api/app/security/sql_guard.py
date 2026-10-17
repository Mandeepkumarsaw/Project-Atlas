import re
from typing import Any, Tuple
from sqlalchemy import text
from app.services.db import ro_engine, record_security_event

FORBIDDEN_SQL_KEYWORDS = [
    r"\binsert\b", r"\bupdate\b", r"\bdelete\b", r"\bdrop\b", r"\balter\b", 
    r"\btruncate\b", r"\bcreate\b", r"\bgrant\b", r"\brevoke\b", r"\bexecute\b",
    r"\bexec\b", r"\binto\s+outfile\b", r"\bload_file\b"
]

SENSITIVE_TABLES = [
    r"\bclients\b", r"\bsecurity_events\b", r"\brequest_logs\b", 
    r"\bclient_report_entitlements\b"
]

SENSITIVE_COLUMNS = [
    r"\bcontact_email\b", r"\bemail\b", r"\bpassword\b", r"\bsecret\b"
]


def validate_and_execute_safe_sql(
    query_str: str, 
    client_id: str | None = None
) -> Tuple[bool, list[dict[str, Any]] | str]:
    """
    Validate query against strict read-only and allowlist rules, then execute on read-only engine.
    """
    lower_query = query_str.lower().strip()

    # 1. Must start with SELECT or WITH
    if not (lower_query.startswith("select") or lower_query.startswith("with")):
        record_security_event(
            event_type="UNSAFE_SQL_ATTEMPT",
            severity="HIGH",
            payload_summary=f"Non-SELECT statement rejected: {query_str[:200]}",
            client_id=client_id
        )
        return False, "Security Error: Only read-only SELECT queries are permitted. Destructive SQL writes and DDL statements are strictly forbidden."

    # 2. Check for forbidden DDL / DML keywords
    for pattern in FORBIDDEN_SQL_KEYWORDS:
        if re.search(pattern, lower_query):
            record_security_event(
                event_type="UNSAFE_SQL_ATTEMPT",
                severity="CRITICAL",
                payload_summary=f"Destructive SQL keyword '{pattern}' detected: {query_str[:200]}",
                client_id=client_id
            )
            return False, "Security Error: Destructive, modifying, or administrative SQL keywords are strictly forbidden."

    # 3. Check for sensitive tables
    for pattern in SENSITIVE_TABLES:
        if re.search(pattern, lower_query):
            record_security_event(
                event_type="CONFIDENTIAL_DATA_LEAK_PREVENTED",
                severity="HIGH",
                payload_summary=f"Unauthorized access to sensitive table matching '{pattern}': {query_str[:200]}",
                client_id=client_id
            )
            return False, "Security Error: Access to client identity, billing, or security audit tables is restricted."

    # 4. Check for sensitive columns
    for pattern in SENSITIVE_COLUMNS:
        if re.search(pattern, lower_query):
            record_security_event(
                event_type="CONFIDENTIAL_DATA_LEAK_PREVENTED",
                severity="HIGH",
                payload_summary=f"Unauthorized access to confidential column matching '{pattern}': {query_str[:200]}",
                client_id=client_id
            )
            return False, "Security Error: Access to client contact emails or confidential fields is restricted."

    # 5. Execute on read-only database connection
    try:
        with ro_engine.connect() as conn:
            result = conn.execute(text(query_str))
            keys = result.keys()
            rows = [dict(zip(keys, row)) for row in result.fetchmany(100)]
            return True, rows
    except Exception as e:
        return False, f"Database query execution error: {str(e)}"
