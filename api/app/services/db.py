import os
import uuid
from datetime import datetime
from typing import Any, Sequence
from sqlalchemy import (
    create_engine, Column, String, Integer, Text, Boolean, 
    DateTime, ForeignKey, DECIMAL, Enum as SQLEnum, JSON, text
)
from sqlalchemy.orm import declarative_base, sessionmaker, relationship, Session
from app.config import settings

Base = declarative_base()

class Client(Base):
    __tablename__ = "clients"
    client_id = Column(String(32), primary_key=True)
    company_name = Column(String(255), nullable=False)
    contact_email = Column(String(255), nullable=False)  # Confidential
    tier = Column(String(50), default="Standard")
    created_at = Column(DateTime, default=datetime.utcnow)

class Report(Base):
    __tablename__ = "reports"
    report_id = Column(String(32), primary_key=True)
    title = Column(String(255), nullable=False)
    sector = Column(String(100), nullable=False)
    publisher = Column(String(150), nullable=False)
    access_type = Column(String(50), default="paid")  # 'paid', 'public_excerpt', 'open'
    publication_date = Column(DateTime, nullable=False)
    pages_count = Column(Integer, default=20)
    summary = Column(Text, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow)

class ClientReportEntitlement(Base):
    __tablename__ = "client_report_entitlements"
    client_id = Column(String(32), ForeignKey("clients.client_id"), primary_key=True)
    report_id = Column(String(32), ForeignKey("reports.report_id"), primary_key=True)
    granted_at = Column(DateTime, default=datetime.utcnow)
    expires_at = Column(DateTime, nullable=True)

class MarketMetric(Base):
    __tablename__ = "market_metrics"
    id = Column(Integer, primary_key=True, autoincrement=True)
    report_id = Column(String(32), ForeignKey("reports.report_id"), nullable=False)
    metric_name = Column(String(150), nullable=False)
    metric_value = Column(DECIMAL(12, 2), nullable=False)
    unit = Column(String(50), default="USD Billion")
    base_year = Column(Integer, nullable=False)
    forecast_year = Column(Integer, nullable=True)
    cagr_percent = Column(DECIMAL(5, 2), nullable=True)
    geographic_scope = Column(String(100), default="Global")
    market_scope = Column(String(255), nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow)

class Conversation(Base):
    __tablename__ = "conversations"
    conversation_id = Column(String(64), primary_key=True)
    client_id = Column(String(32), ForeignKey("clients.client_id"), nullable=False)
    title = Column(String(255), default="New Consultation")
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

class ChatMessage(Base):
    __tablename__ = "chat_messages"
    id = Column(Integer, primary_key=True, autoincrement=True)
    conversation_id = Column(String(64), ForeignKey("conversations.conversation_id"), nullable=False)
    client_id = Column(String(32), nullable=False)
    role = Column(String(20), nullable=False)  # 'user', 'assistant', 'system'
    content = Column(Text, nullable=False)
    citations_json = Column(JSON, nullable=True)
    confidence = Column(DECIMAL(4, 3), nullable=True)
    conflicts_json = Column(JSON, nullable=True)
    derived_values_json = Column(JSON, nullable=True)
    status = Column(String(20), default="success")  # 'success', 'escalated', 'refused', 'error'
    created_at = Column(DateTime, default=datetime.utcnow)

class Feedback(Base):
    __tablename__ = "feedback"
    id = Column(Integer, primary_key=True, autoincrement=True)
    message_id = Column(Integer, ForeignKey("chat_messages.id"), nullable=False)
    conversation_id = Column(String(64), nullable=False)
    client_id = Column(String(32), nullable=False)
    rating = Column(Integer, nullable=False)  # 1 or -1
    comments = Column(Text, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow)

class Escalation(Base):
    __tablename__ = "escalations"
    ticket_id = Column(String(64), primary_key=True)
    conversation_id = Column(String(64), nullable=False)
    client_id = Column(String(32), ForeignKey("clients.client_id"), nullable=False)
    question = Column(Text, nullable=False)
    reason = Column(String(255), nullable=False)
    status = Column(String(20), default="OPEN")  # 'OPEN', 'IN_REVIEW', 'RESOLVED'
    analyst_assigned = Column(String(100), default="Senior Research Analyst")
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

class SecurityEvent(Base):
    __tablename__ = "security_events"
    id = Column(Integer, primary_key=True, autoincrement=True)
    client_id = Column(String(32), nullable=True)
    event_type = Column(String(100), nullable=False)
    severity = Column(String(20), default="MEDIUM")  # 'LOW', 'MEDIUM', 'HIGH', 'CRITICAL'
    payload_summary = Column(Text, nullable=False)
    detected_at = Column(DateTime, default=datetime.utcnow)

class RequestLog(Base):
    __tablename__ = "request_logs"
    id = Column(Integer, primary_key=True, autoincrement=True)
    request_id = Column(String(64), nullable=False)
    client_id = Column(String(32), nullable=False)
    endpoint = Column(String(100), nullable=False)
    router_decision = Column(String(50), nullable=True)
    latency_ms = Column(Integer, nullable=False)
    tokens_used = Column(Integer, default=0)
    estimated_cost_usd = Column(DECIMAL(8, 6), default=0.000000)
    status_code = Column(Integer, nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow)


# Engine and Session management with fallback
def create_app_engine():
    try:
        url = settings.get_database_url(readonly=False)
        engine = create_engine(url, pool_recycle=3600, pool_pre_ping=True)
        # Test connection
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        return engine
    except Exception as e:
        # Fallback to local SQLite for seamless standalone evaluation/testing
        sqlite_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "database", "atlas_local.db"))
        os.makedirs(os.path.dirname(sqlite_path), exist_ok=True)
        sqlite_url = f"sqlite:///{sqlite_path}"
        engine = create_engine(sqlite_url, connect_args={"check_same_thread": False})
        Base.metadata.create_all(engine)
        return engine


def create_ro_engine():
    try:
        url = settings.get_database_url(readonly=True)
        engine = create_engine(url, pool_recycle=3600, pool_pre_ping=True)
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        return engine
    except Exception:
        # Fallback to main engine
        return create_app_engine()


app_engine = create_app_engine()
ro_engine = create_ro_engine()

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=app_engine)
ROSessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=ro_engine)


def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


def get_ro_db():
    db = ROSessionLocal()
    try:
        yield db
    finally:
        db.close()


def init_database_tables():
    """Ensure all tables are created and seed data is populated if empty."""
    Base.metadata.create_all(bind=app_engine)
    with SessionLocal() as db:
        # Check if seed clients exist
        c_count = db.query(Client).count()
        if c_count == 0:
            # Seed default clients
            c1 = Client(client_id="C-01", company_name="Northwind Utilities", contact_email="sarah.jenkins@northwindutilities.com", tier="Enterprise")
            c2 = Client(client_id="C-02", company_name="Blue Delta Infra", contact_email="marcus.vance@bluedeltainfra.com", tier="Standard")
            c3 = Client(client_id="C-03", company_name="Apex Energy Partners", contact_email="elena.rostova@apexenergy.com", tier="Trial")
            db.add_all([c1, c2, c3])
            
            # Seed default reports
            r1 = Report(report_id="R-101", title="Global Smart Water Meter Market 2025", sector="Utilities & Infrastructure", publisher="Cognitive Market Research", access_type="paid", publication_date=datetime(2025, 1, 15), pages_count=24, summary="Full-scope solution report.")
            r2 = Report(report_id="R-102", title="Smart Water Metering: Industry Outlook 2024", sector="Utilities & Infrastructure", publisher="WaterTech Horizon Research", access_type="public_excerpt", publication_date=datetime(2024, 11, 20), pages_count=15, summary="Hardware-only report with public preview.")
            r3 = Report(report_id="R-103", title="Residential Water Purifier Market 2025", sector="Consumer Durables", publisher="Consumer Goods Intelligence", access_type="paid", publication_date=datetime(2025, 2, 10), pages_count=30, summary="Point-of-use consumer purification.")
            r4 = Report(report_id="R-104", title="Commercial Building Energy Management Market 2025", sector="Building Technologies & Utilities", publisher="Smart Facility Analytics", access_type="paid", publication_date=datetime(2025, 3, 5), pages_count=22, summary="Synthetic BEMS study.")
            r5 = Report(report_id="R-105", title="Industrial Water Treatment Equipment Market 2025", sector="Industrial Equipment & Environment", publisher="Industrial Resource Horizons", access_type="paid", publication_date=datetime(2025, 1, 28), pages_count=28, summary="Synthetic industrial water study.")
            r6 = Report(report_id="R-106", title="Smart Grid Analytics Market 2025", sector="Utilities & Infrastructure", publisher="GridTech Insights", access_type="paid", publication_date=datetime(2025, 2, 24), pages_count=20, summary="Synthetic smart grid analytics study.")
            db.add_all([r1, r2, r3, r4, r5, r6])
            
            # Seed entitlements:
            # C-01: R-101 and R-103
            # C-02: R-103
            e1 = ClientReportEntitlement(client_id="C-01", report_id="R-101")
            e2 = ClientReportEntitlement(client_id="C-01", report_id="R-103")
            e3 = ClientReportEntitlement(client_id="C-02", report_id="R-103")
            db.add_all([e1, e2, e3])
            
            # Seed metrics
            m1 = MarketMetric(report_id="R-101", metric_name="Global Market Size 2024", metric_value=4.21, unit="USD Billion", base_year=2024, forecast_year=2030, cagr_percent=11.20, geographic_scope="Global", market_scope="Full Solution: Hardware, Software & Managed Services")
            m2 = MarketMetric(report_id="R-101", metric_name="APAC Market Share 2024", metric_value=38.00, unit="Percent", base_year=2024, forecast_year=2024, cagr_percent=None, geographic_scope="Asia-Pacific", market_scope="Regional share of global market")
            m3 = MarketMetric(report_id="R-102", metric_name="Global Hardware Market Size 2024", metric_value=3.87, unit="USD Billion", base_year=2024, forecast_year=2029, cagr_percent=9.80, geographic_scope="Global", market_scope="Hardware Only: AMR/AMI Physical Devices Exclusively")
            m4 = MarketMetric(report_id="R-102", metric_name="APAC Hardware Share 2024", metric_value=41.00, unit="Percent", base_year=2024, forecast_year=2024, cagr_percent=None, geographic_scope="Asia-Pacific", market_scope="Regional share of physical hardware shipments")
            m5 = MarketMetric(report_id="R-103", metric_name="Global Residential Water Purifier Market Size 2024", metric_value=12.45, unit="USD Billion", base_year=2024, forecast_year=2030, cagr_percent=8.40, geographic_scope="Global", market_scope="Point-of-Use & Point-of-Entry Appliances")
            m6 = MarketMetric(report_id="R-103", metric_name="APAC Market Share 2024", metric_value=45.00, unit="Percent", base_year=2024, forecast_year=2024, cagr_percent=None, geographic_scope="Asia-Pacific", market_scope="Regional market share")
            m7 = MarketMetric(report_id="R-104", metric_name="Global BEMS Market Size 2024", metric_value=8.70, unit="USD Billion", base_year=2024, forecast_year=2030, cagr_percent=12.10, geographic_scope="Global", market_scope="Commercial Building Energy Software & Controls")
            m8 = MarketMetric(report_id="R-105", metric_name="Global Industrial Water Treatment Market Size 2024", metric_value=15.30, unit="USD Billion", base_year=2024, forecast_year=2030, cagr_percent=6.90, geographic_scope="Global", market_scope="Industrial Filtration, Membranes & ZLD Equipment")
            m9 = MarketMetric(report_id="R-106", metric_name="Global Smart Grid Analytics Market Size 2024", metric_value=5.60, unit="USD Billion", base_year=2024, forecast_year=2030, cagr_percent=14.50, geographic_scope="Global", market_scope="ADMS, DERMS & Predictive Asset Analytics")
            db.add_all([m1, m2, m3, m4, m5, m6, m7, m8, m9])
            
            db.commit()


# Helper service functions
def get_client_entitlements(client_id: str) -> set[str]:
    """
    Return set of report IDs accessible to the client.
    Includes purchased reports from client_report_entitlements plus public_excerpt / open reports.
    """
    with SessionLocal() as db:
        purchased = db.query(ClientReportEntitlement.report_id).filter(
            ClientReportEntitlement.client_id == client_id
        ).all()
        purchased_ids = {row[0] for row in purchased}
        
        # Public excerpt reports are readable across all clients
        public_reports = db.query(Report.report_id).filter(
            Report.access_type.in_(["public_excerpt", "open"])
        ).all()
        public_ids = {row[0] for row in public_reports}
        
        return purchased_ids.union(public_ids)


def get_all_reports() -> list[dict[str, Any]]:
    """Return all report metadata in catalog."""
    with SessionLocal() as db:
        reports = db.query(Report).all()
        return [
            {
                "report_id": r.report_id,
                "title": r.title,
                "sector": r.sector,
                "publisher": r.publisher,
                "access_type": r.access_type,
                "publication_date": r.publication_date.strftime("%Y-%m-%d") if r.publication_date else "",
                "pages_count": r.pages_count,
                "summary": r.summary
            }
            for r in reports
        ]


def create_escalation_ticket(client_id: str, conversation_id: str, question: str, reason: str) -> str:
    """Create a new analyst escalation ticket in MySQL."""
    ticket_id = f"ESC-{uuid.uuid4().hex[:8].upper()}"
    with SessionLocal() as db:
        esc = Escalation(
            ticket_id=ticket_id,
            conversation_id=conversation_id,
            client_id=client_id,
            question=question,
            reason=reason,
            status="OPEN",
            analyst_assigned="Senior Research Analyst"
        )
        db.add(esc)
        db.commit()
    return ticket_id


def record_security_event(event_type: str, payload_summary: str, severity: str = "MEDIUM", client_id: str | None = None):
    """Log security anomaly / injection trap / access denial."""
    with SessionLocal() as db:
        evt = SecurityEvent(
            client_id=client_id,
            event_type=event_type,
            severity=severity,
            payload_summary=payload_summary[:1000]
        )
        db.add(evt)
        db.commit()


def record_chat_message(
    conversation_id: str,
    client_id: str,
    role: str,
    content: str,
    citations: list[dict] | None = None,
    confidence: float | None = None,
    conflicts: list[str] | None = None,
    derived_values: list[dict] | None = None,
    status: str = "success"
) -> int:
    """Record chat history to MySQL."""
    with SessionLocal() as db:
        # Ensure conversation exists
        conv = db.query(Conversation).filter(Conversation.conversation_id == conversation_id).first()
        if not conv:
            conv = Conversation(conversation_id=conversation_id, client_id=client_id)
            db.add(conv)
            db.flush()
        
        msg = ChatMessage(
            conversation_id=conversation_id,
            client_id=client_id,
            role=role,
            content=content,
            citations_json=citations,
            confidence=confidence,
            conflicts_json=conflicts,
            derived_values_json=derived_values,
            status=status
        )
        db.add(msg)
        db.commit()
        return msg.id


def record_request_log(
    request_id: str,
    client_id: str,
    endpoint: str,
    latency_ms: int,
    status_code: int,
    router_decision: str | None = None,
    tokens_used: int = 0,
    estimated_cost_usd: float = 0.0
):
    """Log API request metrics to MySQL."""
    with SessionLocal() as db:
        log_entry = RequestLog(
            request_id=request_id,
            client_id=client_id,
            endpoint=endpoint,
            router_decision=router_decision,
            latency_ms=latency_ms,
            tokens_used=tokens_used,
            estimated_cost_usd=estimated_cost_usd,
            status_code=status_code
        )
        db.add(log_entry)
        db.commit()
