import pytest
from app.security.entitlements import get_authorized_reports, check_report_access


def test_entitlements_c01_vs_c02():
    c01_reports = get_authorized_reports("C-01")
    c02_reports = get_authorized_reports("C-02")

    # C-01 holds R-101 and R-103
    assert "R-101" in c01_reports
    assert "R-103" in c01_reports

    # C-02 holds R-103 only; R-101 must NOT be authorized
    assert "R-103" in c02_reports
    assert "R-101" not in c02_reports

    # Both clients can view public excerpt R-102
    assert "R-102" in c01_reports
    assert "R-102" in c02_reports

    # Check access verification helper
    allowed, msg = check_report_access("C-02", "R-101")
    assert not allowed
    assert "not currently hold an active license" in msg
