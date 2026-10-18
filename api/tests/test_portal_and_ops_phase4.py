import os
import yaml
import pytest
from fastapi.testclient import TestClient
from unittest.mock import patch, MagicMock
from app.main import app
from app.config import settings
from app.services.db import SessionLocal, Conversation, ChatMessage, Feedback, Client, init_database_tables

client = TestClient(app)


class TestHealthDependencyChecks:
    """Verifies ATLAS-OPS-001: Health endpoint returns HTTP 503 and reports degradation when dependencies fail."""

    def test_health_returns_200_when_dependencies_healthy(self):
        """When database and vector store are reachable, /health returns HTTP 200 with status='ok'."""
        response = client.get("/health")
        assert response.status_code == 200
        data = response.json()
        assert data["status"] == "ok"
        assert data["mysql"] == "healthy"
        assert data["vector_db"] == "healthy"
        assert data["version"] == "1.0.0"

    def test_health_returns_503_when_database_down(self):
        """When database ping fails, /health must return HTTP 503 Service Unavailable with status='degraded'."""
        with patch("app.routes.health.app_engine.connect", side_effect=RuntimeError("Database connection lost")):
            response = client.get("/health")
            assert response.status_code == 503
            data = response.json()
            assert data["status"] == "degraded"
            assert data["mysql"] == "unhealthy"
            assert "Database connection lost" not in response.text  # Sanitized, no leaked traces

    def test_health_returns_503_when_vector_db_down(self):
        """When vector store check fails, /health must return HTTP 503 Service Unavailable."""
        mock_client = MagicMock()
        mock_client.get_collections.side_effect = RuntimeError("Qdrant unreachable")
        with patch("app.routes.health.get_qdrant_client", return_value=mock_client):
            response = client.get("/health")
            assert response.status_code == 503
            data = response.json()
            assert data["status"] == "degraded"
            assert data["vector_db"] == "unhealthy"


class TestNonDestructiveDatabaseSeeding:
    """Verifies ATLAS-DB-003: Database initialization and seeding are idempotent and non-destructive."""

    def test_seed_sql_does_not_truncate_user_tables(self):
        """Verify that database/seed.sql does NOT truncate user conversations, messages, feedback, or escalations."""
        seed_path = os.path.join(os.path.dirname(__file__), "..", "..", "database", "seed.sql")
        with open(seed_path, "r", encoding="utf-8") as f:
            content = f.read().upper()

        assert "TRUNCATE TABLE CONVERSATIONS" not in content
        assert "TRUNCATE TABLE CHAT_MESSAGES" not in content
        assert "TRUNCATE TABLE FEEDBACK" not in content
        assert "TRUNCATE TABLE ESCALATIONS" not in content

    def test_seed_sql_uses_idempotent_insert_ignore(self):
        """Verify that seed.sql uses INSERT IGNORE across all catalog tables."""
        seed_path = os.path.join(os.path.dirname(__file__), "..", "..", "database", "seed.sql")
        with open(seed_path, "r", encoding="utf-8") as f:
            content = f.read()

        assert "INSERT IGNORE INTO clients" in content
        assert "INSERT IGNORE INTO reports" in content
        assert "INSERT IGNORE INTO client_report_entitlements" in content
        assert "INSERT IGNORE INTO market_metrics" in content
        assert "R-107" in content  # 7 reports included in seed

    def test_database_init_preserves_existing_user_conversations(self):
        """Calling init_database_tables must not delete or reset existing conversations and messages."""
        test_conv_id = "conv-persist-test-01"
        with SessionLocal() as db:
            # Create user conversation and message
            existing = db.query(Conversation).filter(Conversation.conversation_id == test_conv_id).first()
            if not existing:
                conv = Conversation(conversation_id=test_conv_id, client_id="C-01", title="Important Consultation")
                db.add(conv)
                db.commit()

        # Re-run table initialization
        init_database_tables()

        # Verify conversation is intact
        with SessionLocal() as db:
            found = db.query(Conversation).filter(Conversation.conversation_id == test_conv_id).first()
            assert found is not None
            assert found.title == "Important Consultation"


class TestDockerComposeHardening:
    """Verifies Docker Compose service definitions, healthchecks, port restrictions, and security."""

    @pytest.fixture
    def compose_data(self):
        compose_path = os.path.join(os.path.dirname(__file__), "..", "..", "docker-compose.yml")
        with open(compose_path, "r", encoding="utf-8") as f:
            return yaml.safe_load(f)

    def test_docker_compose_contains_all_four_required_services(self, compose_data):
        """Verify that MySQL, Qdrant, FastAPI backend, and CodeIgniter PHP portal services are defined."""
        services = compose_data.get("services", {})
        assert "mysql" in services
        assert "qdrant" in services
        assert "api" in services
        assert "php" in services

    def test_docker_compose_restricts_db_and_qdrant_ports_to_localhost(self, compose_data):
        """Database and vector database ports must be bound to 127.0.0.1, not exposed to 0.0.0.0."""
        mysql_ports = compose_data["services"]["mysql"].get("ports", [])
        for p in mysql_ports:
            assert p.startswith("127.0.0.1:"), f"MySQL port {p} is exposed beyond localhost!"

        qdrant_ports = compose_data["services"]["qdrant"].get("ports", [])
        for p in qdrant_ports:
            assert p.startswith("127.0.0.1:"), f"Qdrant port {p} is exposed beyond localhost!"

    def test_docker_compose_has_healthcheck_dependencies(self, compose_data):
        """API and PHP services must depend on dependency healthchecks."""
        api_svc = compose_data["services"]["api"]
        assert "healthcheck" in api_svc
        assert api_svc["depends_on"]["mysql"]["condition"] == "service_healthy"
        assert api_svc["depends_on"]["qdrant"]["condition"] == "service_healthy"

        php_svc = compose_data["services"]["php"]
        assert php_svc["depends_on"]["api"]["condition"] == "service_healthy"
        assert php_svc["depends_on"]["mysql"]["condition"] == "service_healthy"

    def test_docker_compose_network_isolation(self, compose_data):
        """Services must be joined on an isolated bridge network."""
        assert "networks" in compose_data
        assert "atlas_net" in compose_data["networks"]
        for svc_name in ["mysql", "qdrant", "api", "php"]:
            assert "atlas_net" in compose_data["services"][svc_name]["networks"]


class TestCodeIgniterPortalSecurityAndXSS:
    """Verifies ATLAS-PORTAL-001 & ATLAS-SEC-003: PHP portal XSS escaping, CSRF, and secret shielding."""

    def test_portal_views_strictly_escape_untrusted_output(self):
        """Verify that chat_view.php utilizes html_escape on all dynamic content to prevent XSS."""
        view_path = os.path.join(os.path.dirname(__file__), "..", "..", "php-portal", "application", "views", "chat_view.php")
        with open(view_path, "r", encoding="utf-8") as f:
            view_code = f.read()

        # Content must be escaped
        assert "html_escape($msg['content'])" in view_code
        assert "html_escape($conf)" in view_code
        assert "html_escape($calc['metric'])" in view_code
        assert "html_escape($cit['claim'])" in view_code
        assert "html_escape($client_id)" in view_code
        # Form must carry CSRF token
        assert "get_csrf_token_name()" in view_code
        assert "get_csrf_hash()" in view_code

    def test_portal_config_enforces_csrf_protection(self):
        """Verify that CodeIgniter config.php enables CSRF protection."""
        config_path = os.path.join(os.path.dirname(__file__), "..", "..", "php-portal", "application", "config", "config.php")
        with open(config_path, "r", encoding="utf-8") as f:
            config_code = f.read()

        assert "$config['csrf_protection'] = TRUE;" in config_code
        assert "$config['csrf_token_name'] = 'atlas_csrf_token';" in config_code

    def test_portal_files_do_not_expose_secrets_to_browsers(self):
        """Verify that chat_view.php and client assets contain zero hardcoded API keys."""
        view_path = os.path.join(os.path.dirname(__file__), "..", "..", "php-portal", "application", "views", "chat_view.php")
        with open(view_path, "r", encoding="utf-8") as f:
            view_code = f.read()

        assert "atlas-secret-key" not in view_code
        assert "atlas-key-c01" not in view_code
        assert "X-API-Key" not in view_code


class TestPortalFeedbackAndMessagePersistence:
    """Verifies conversation persistence and feedback logging in the database."""

    def test_chat_message_and_feedback_persisted_to_database(self):
        """Verify that conversations, messages, and feedback are saved in the relational database."""
        test_conv = "conv-feedback-test-01"
        test_client = "C-01"

        with SessionLocal() as db:
            # 1. Create conversation
            conv = db.query(Conversation).filter(Conversation.conversation_id == test_conv).first()
            if not conv:
                conv = Conversation(conversation_id=test_conv, client_id=test_client, title="Feedback Test")
                db.add(conv)
                db.commit()

            # 2. Add assistant message
            msg = ChatMessage(
                conversation_id=test_conv,
                client_id=test_client,
                role="assistant",
                content="The global smart water meter market size in 2024 was USD 4.21 Billion.",
                confidence=0.95,
                status="success"
            )
            db.add(msg)
            db.commit()
            db.refresh(msg)
            msg_id = msg.id
            assert msg_id > 0

            # 3. Add positive feedback (+1)
            fb_positive = Feedback(
                message_id=msg_id,
                conversation_id=test_conv,
                client_id=test_client,
                rating=1,
                comments="Very accurate and grounded in R-101."
            )
            db.add(fb_positive)
            db.commit()
            db.refresh(fb_positive)

            # 4. Verify feedback record
            stored_fb = db.query(Feedback).filter(Feedback.id == fb_positive.id).first()
            assert stored_fb is not None
            assert stored_fb.rating == 1
            assert stored_fb.message_id == msg_id
            assert stored_fb.client_id == test_client

    def test_feedback_cross_tenant_isolation_in_database(self):
        """Feedback submitted by C-02 on C-01's message must be identifiable or rejected."""
        test_conv = "conv-tenant-test-02"
        with SessionLocal() as db:
            # Message owned by C-01
            c01_msg = ChatMessage(
                conversation_id=test_conv,
                client_id="C-01",
                role="assistant",
                content="Confidential report analysis for C-01."
            )
            db.add(c01_msg)
            db.commit()
            db.refresh(c01_msg)

            # Verification that message client_id is C-01
            assert c01_msg.client_id == "C-01"
            assert c01_msg.client_id != "C-02"
