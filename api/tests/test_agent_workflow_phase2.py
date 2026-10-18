import pytest
from app.services.db import ro_engine, app_engine, SessionLocal, Report, Client, Escalation
from app.security.sql_guard import validate_and_execute_safe_sql
from app.agents.calculator import calculate_market_projection
from app.agents.critic import evaluate_response_grounding
from app.agents.graph import run_agent_workflow
from app.schemas.chat import Citation
from sqlalchemy import text


class TestPhase2SQLSafetyAndFailClosed:
    """Verifies SQL tool fail-closed behavior, AST protections, and database state integrity."""

    def test_ro_engine_blocks_writes_at_db_driver_level(self):
        """Verify that the read-only engine connection driver actively blocks write statements."""
        with ro_engine.connect() as conn:
            with pytest.raises(Exception) as exc_info:
                conn.execute(text("CREATE TABLE hack_test (id INT)"))
            err = str(exc_info.value).lower()
            assert "readonly" in err or "read-only" in err or "denied" in err

    def test_sql_guard_rejects_all_destructive_dml_and_ddl(self):
        """Verify that INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE are blocked with state unchanged."""
        with SessionLocal() as db:
            initial_report_count = db.query(Report).count()
            initial_client_count = db.query(Client).count()

        attacks = [
            "INSERT INTO reports (report_id, title, sector) VALUES ('R-999', 'Hacked', 'Fake')",
            "UPDATE reports SET title = 'Defaced' WHERE report_id = 'R-101'",
            "DELETE FROM reports WHERE report_id = 'R-101'",
            "DROP TABLE reports",
            "TRUNCATE TABLE reports",
            "ALTER TABLE reports ADD COLUMN compromised VARCHAR(50)"
        ]

        for attack in attacks:
            success, result = validate_and_execute_safe_sql(attack, client_id="C-01")
            assert success is False, f"Attack was not blocked: {attack}"
            assert "Security Error" in result or "forbidden" in result.lower()

        # Verify database state remained 100% untouched
        with SessionLocal() as db:
            assert db.query(Report).count() == initial_report_count
            assert db.query(Client).count() == initial_client_count

    def test_sql_guard_rejects_stacked_queries_and_comments(self):
        """Verify that multi-statement queries and comment obfuscation are rejected."""
        stacked = "SELECT * FROM reports; DROP TABLE reports;"
        success, result = validate_and_execute_safe_sql(stacked, client_id="C-01")
        assert success is False
        assert "Multiple stacked SQL statements are strictly forbidden" in result

        comment_attack = "SELECT * FROM reports -- hide rest\nWHERE 1=1"
        success2, result2 = validate_and_execute_safe_sql(comment_attack, client_id="C-01")
        assert success2 is False
        assert "SQL comment syntax" in result2

    def test_sql_guard_blocks_confidential_client_tables(self):
        """Verify access to clients table and contact_email column is blocked."""
        forbidden_queries = [
            "SELECT * FROM clients",
            "SELECT contact_email FROM clients",
            "SELECT client_id, contact_email FROM clients",
            "SELECT * FROM security_events",
            "SELECT * FROM request_logs"
        ]
        for q in forbidden_queries:
            success, result = validate_and_execute_safe_sql(q, client_id="C-01")
            assert success is False
            assert "restricted" in result.lower()

    def test_approved_read_only_catalog_query_succeeds(self):
        """Verify legitimate read-only aggregation queries execute successfully."""
        query = "SELECT sector, COUNT(*) as report_count FROM reports GROUP BY sector ORDER BY report_count DESC"
        success, rows = validate_and_execute_safe_sql(query, client_id="C-01")
        assert success is True
        assert isinstance(rows, list)
        assert len(rows) > 0
        assert "sector" in rows[0]
        assert "report_count" in rows[0]


class TestPhase2CalculatorAccuracyAndGuardrails:
    """Verifies deterministic math, interval disambiguation, and horizon guardrails."""

    def test_r101_six_interval_global_and_apac_projections(self):
        """
        Verify R-101 2030 projection:
        Global: 4.21 * (1 + 0.112)^6 = 7.98 Billion
        APAC (38% share): 7.98 * 0.38 = 3.03 Billion
        """
        success, derived, explanation = calculate_market_projection("What will the APAC smart water meter market be worth in 2030?")
        assert success is True
        assert derived is not None
        
        # Check explicit figures in derived dictionary
        assert "3.02" in derived["scenarios"]["APAC_Primary_6_Intervals"] or "3.03" in derived["scenarios"]["APAC_Primary_6_Intervals"]
        assert "7.96" in derived["scenarios"]["Global_Primary_6_Intervals"] or "7.98" in derived["scenarios"]["Global_Primary_6_Intervals"]
        assert "USD 2.72 Billion" in derived["scenarios"]["APAC_Alternative_5_Intervals"]
        
        # Verify that USD 7.08B / USD 7.16B is NEVER mislabeled as an APAC estimate
        assert "APAC" not in derived["scenarios"]["Global_Alternative_5_Intervals"]
        assert "7.16" in derived["scenarios"]["Global_Alternative_5_Intervals"] or "7.08" in derived["scenarios"]["Global_Alternative_5_Intervals"]
        assert "USD 7.08B" in explanation or "USD 7.16" in explanation
        assert "USD 3.03" in explanation or "USD 3.02" in explanation
        assert "Base-Year Ambiguity" in explanation

    def test_calculator_horizon_guardrail_refuses_2035_extrapolation(self):
        """Verify query requesting 2035 forecast refuses silent compounding and flags escalation."""
        success, derived, explanation = calculate_market_projection("What will the market be worth in 2035?")
        assert success is False
        assert derived is None
        assert "Escalation Required" in explanation
        assert "2030" in explanation
        assert "2035" in explanation

    def test_calculator_accepts_dynamic_parameters(self):
        """Verify calculator supports arbitrary validated parameters for other sectors."""
        success, derived, explanation = calculate_market_projection(
            question="Calculate projected market value",
            base_value=10.0,
            cagr=0.10,
            base_year=2024,
            target_year=2028,
            share=0.50,
            market_scope="Industrial Automation Sensors",
            source_report_id="R-107",
            source_page=6
        )
        assert success is True
        assert derived is not None
        # 10.0 * (1.10)^4 = 14.641
        assert "14.64" in derived["scenarios"]["Global_Primary_6_Intervals"]
        # 14.641 * 0.50 = 7.3205
        assert "7.32" in derived["scenarios"]["APAC_Primary_6_Intervals"]


class TestPhase2AgentWorkflowAndCritic:
    """Verifies end-to-end routing, entitlement filtering, conflict handling, and escalation."""

    def test_c02_entitlement_enforcement_prevents_r101_leak(self):
        """Verify Client C-02 (standard tier) asking about water meters never receives R-101 figures."""
        out = run_agent_workflow(
            client_id="C-02",
            conversation_id="test-c02-entitlements",
            question="What is the 2024 global market size in R-101?"
        )
        answer = out["answer"]
        citations = out["citations"]
        
        # Must not reveal USD 4.21B or cite R-101
        assert "4.21" not in answer
        assert not any(c.report_id == "R-101" for c in citations)
        # Must offer upgrade guidance
        assert "license" in answer.lower() or "upgrade" in answer.lower() or "subscription" in answer.lower()

    def test_source_conflict_handling_prohibits_averaging(self):
        """Verify C-01 asking about differing market sizes receives scope breakdown and anti-averaging warning."""
        out = run_agent_workflow(
            client_id="C-01",
            conversation_id="test-c01-conflict",
            question="What was the global smart water meter market size in 2024?"
        )
        answer = out["answer"]
        conflicts = out["conflicts"]
        
        # Must mention both figures
        assert "4.21" in answer
        assert "3.87" in answer
        # Must explain scope differences
        assert "hardware" in answer.lower()
        assert "software" in answer.lower()
        # Must warn against averaging
        assert "not be averaged" in answer.lower() or "must not be averaged" in answer.lower()
        assert len(conflicts) > 0

    def test_critic_slashes_confidence_if_averaging_endorsed(self):
        """Verify critic sets confidence to 0.30 and triggers escalation if draft tries to average."""
        mock_draft = "Taking the average gives USD 4.04 billion for the global market size."
        citations = [
            Citation(report_id="R-101", page=4, claim="4.21B"),
            Citation(report_id="R-102", page=6, claim="3.87B")
        ]
        conf, conflicts, should_esc, reason = evaluate_response_grounding(
            question="What is the average market size?",
            draft_answer=mock_draft,
            citations=citations,
            evidence_chunks=[],
            allowed_report_ids={"R-101", "R-102"}
        )
        assert conf <= 0.40
        assert should_esc is True
        assert "improperly endorsed averaging" in reason

    def test_forecast_2035_triggers_escalation_ticket(self):
        """Verify 2035 forecast inquiry creates a persistent MySQL ticket and sets escalated=True."""
        out = run_agent_workflow(
            client_id="C-01",
            conversation_id="test-2035-escalation",
            question="What will the market be worth in 2035?"
        )
        assert out["escalated"] is True
        assert out["ticket_id"] is not None
        assert out["ticket_id"].startswith("ESC-")
        assert "2030" in out["answer"]  # Confirms note that study horizon ends in 2030

        # Verify persistent ticket exists in database
        with SessionLocal() as db:
            ticket = db.query(Escalation).filter(Escalation.ticket_id == out["ticket_id"]).first()
            assert ticket is not None
            assert ticket.client_id == "C-01"
            assert "2030" in ticket.reason or "horizon" in ticket.reason.lower() or "exceed" in ticket.reason.lower()
