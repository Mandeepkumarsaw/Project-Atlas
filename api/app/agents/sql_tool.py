import re
from typing import Any, Tuple
from app.security.sql_guard import validate_and_execute_safe_sql


def run_sector_aggregate_query(question: str, client_id: str | None = None) -> Tuple[bool, list[dict[str, Any]] | str, str]:
    """
    Execute read-only SQL aggregate queries to answer catalog distribution questions.
    Validates any custom or destructive SQL queries against strict guardrails.
    """
    q = question.lower().strip()

    # If the user prompt directly attempted an SQL statement (e.g. DROP, DELETE, INSERT, UPDATE, or raw SELECT)
    if any(k in q for k in ["drop", "delete", "insert", "alter", "truncate", "update", "select"]):
        # Validate directly against SQL guard
        success, result = validate_and_execute_safe_sql(question, client_id=client_id)
        if not success:
            return False, result, str(result)
        summary = f"Database query executed successfully. Retrieved {len(result)} record(s)."
        return True, result, summary

    if "sector" in q and ("most" in q or "highest" in q or "count" in q):
        sql = """
            SELECT sector, COUNT(*) as report_count 
            FROM reports 
            GROUP BY sector 
            ORDER BY report_count DESC;
        """
        success, result = validate_and_execute_safe_sql(sql, client_id=client_id)
        if success and isinstance(result, list) and len(result) > 0:
            top_sector = result[0]["sector"]
            count = result[0]["report_count"]
            summary = (
                f"According to the database catalog, the sector with the most reports is "
                f"**{top_sector}** with **{count} reports**. Full breakdown across sectors:\n"
            )
            for row in result:
                summary += f"- **{row['sector']}**: {row['report_count']} report(s)\n"
            return True, result, summary

    # General report count query
    sql = "SELECT COUNT(*) as total_reports FROM reports;"
    success, result = validate_and_execute_safe_sql(sql, client_id=client_id)
    if success and isinstance(result, list):
        total = result[0]["total_reports"]
        summary = f"There are currently **{total} market research reports** available in the Project Atlas research library."
        return True, result, summary

    return False, "Query not recognized", "Could not execute requested aggregate query."
