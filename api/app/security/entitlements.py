from typing import Set, Tuple
from app.services.db import get_client_entitlements, get_all_reports, record_security_event


def get_authorized_reports(client_id: str) -> Set[str]:
    """Retrieve allowed report IDs for client from MySQL."""
    return get_client_entitlements(client_id)


def check_report_access(client_id: str, report_id: str) -> Tuple[bool, str | None]:
    """
    Check if client has access to a given report.
    If unauthorized, logs access attempt and provides upgrade guidance.
    """
    allowed = get_authorized_reports(client_id)
    if report_id in allowed:
        return True, None

    # Check if report exists in catalog
    all_reports = {r["report_id"]: r["title"] for r in get_all_reports()}
    if report_id in all_reports:
        record_security_event(
            event_type="UNAUTHORIZED_ACCESS_ATTEMPT",
            severity="MEDIUM",
            payload_summary=f"Client '{client_id}' attempted to access unauthorized report '{report_id}' ({all_reports[report_id]})",
            client_id=client_id
        )
        return False, f"Report {report_id} ('{all_reports[report_id]}') is part of the Cognitive Market Research library, but your organization ('{client_id}') does not currently hold an active license for it. Please contact your account representative or upgrades@cognitivemarketresearch.com to acquire access."

    return False, f"Report '{report_id}' was not found in the library."
