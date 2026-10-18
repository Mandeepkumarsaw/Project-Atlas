import json
import os
import sys
import pytest

# Ensure project root and api directory are in sys.path
PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)
API_DIR = os.path.join(PROJECT_ROOT, "api")
if API_DIR not in sys.path:
    sys.path.insert(0, API_DIR)

from app.services.db import init_database_tables, SessionLocal, Client, Report, ClientReportEntitlement, MarketMetric
from app.security.sql_guard import validate_and_execute_safe_sql
from evaluation.run_evaluation import get_database_row_counts, execute_evaluation


@pytest.fixture(scope="module", autouse=True)
def setup_eval_env():
    init_database_tables()


def test_sql_write_safety_probe_preserves_zero_mutation():
    """Verify that forbidden write statements fail-closed and leave row counts strictly unaltered."""
    forbidden_writes = [
        "INSERT INTO clients (client_id, company_name, contact_email) VALUES ('C-ATTACK', 'Hacked', 'hacked@evil.com')",
        "UPDATE clients SET company_name = 'Defaced' WHERE client_id = 'C-01'",
        "DELETE FROM reports WHERE report_id = 'R-101'",
        "DROP TABLE clients;"
    ]

    for stmt in forbidden_writes:
        snap_before = get_database_row_counts()
        success, err = validate_and_execute_safe_sql(stmt, client_id="C-01")
        snap_after = get_database_row_counts()

        assert success is False, f"Expected forbidden statement to be rejected: {stmt}"
        assert "Security Error" in str(err)
        assert snap_before == snap_after, f"Database table counts mutated during statement execution: {stmt}"


def test_questions_jsonl_dataset_integrity():
    """Verify questions.jsonl meets or exceeds the 25-question minimum and covers all required categories."""
    jsonl_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "evaluation", "questions.jsonl"))
    assert os.path.isfile(jsonl_path), "evaluation/questions.jsonl must exist"

    questions = []
    with open(jsonl_path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if line:
                questions.append(json.loads(line))

    assert len(questions) >= 25, f"Expected at least 25 questions, got {len(questions)}"

    categories = {q["category"] for q in questions}
    required_categories = {
        "factual", "source_conflicts", "calculations", 
        "entitlements", "injection_and_sensitive_data", 
        "sql_safety", "out_of_scope", "escalations"
    }
    missing = required_categories - categories
    assert not missing, f"Missing required question categories: {missing}"

    for q in questions:
        assert "id" in q and "client_id" in q and "question" in q and "category" in q
        assert q["client_id"] in ["C-01", "C-02", "C-03"]


def test_evaluation_benchmark_execution_and_metrics():
    """Verify evaluation harness execution achieves 100% pass rate and zero security breaches."""
    results, summary = execute_evaluation()

    assert summary["total_questions"] >= 25
    assert summary["pass_rate"] == 100.0, f"Expected 100.0% pass rate, got {summary['pass_rate']}%"
    assert summary["entitlement_violations"] == 0
    assert summary["injection_breaches"] == 0
    assert summary["sql_write_breaches"] == 0
    assert summary["conflict_detection_rate"] == 100.0
    assert summary["avg_latency_ms"] < 30000.0  # Under 30s PDF requirement
    assert os.path.isfile(os.path.join(os.path.dirname(__file__), "..", "..", "evaluation", "evaluation_results.json"))
