import pytest
from app.security.sql_guard import validate_and_execute_safe_sql


def test_sql_guard_blocks_destructive_ddl():
    success, msg = validate_and_execute_safe_sql("DROP TABLE clients;")
    assert not success
    assert "Security Error" in msg
    assert "strictly forbidden" in msg


def test_sql_guard_blocks_sensitive_email_table():
    success, msg = validate_and_execute_safe_sql("SELECT contact_email FROM clients;")
    assert not success
    assert "Security Error" in msg
    assert "restricted" in msg


def test_sql_guard_allows_safe_select():
    success, rows = validate_and_execute_safe_sql("SELECT report_id, sector FROM reports LIMIT 5;")
    assert success
    assert isinstance(rows, list)
