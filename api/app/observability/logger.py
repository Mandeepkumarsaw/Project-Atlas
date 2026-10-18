import json
import logging
import sys
import time
from datetime import datetime
from typing import Any
from app.config import settings


class JsonFormatter(logging.Formatter):
    def format(self, record: logging.LogRecord) -> str:
        log_data = {
            "timestamp": datetime.utcnow().isoformat() + "Z",
            "level": record.levelname,
            "logger": record.name,
            "message": record.getMessage()
        }
        if hasattr(record, "extra_fields"):
            log_data.update(record.extra_fields)
        return json.dumps(log_data)


def setup_observability_logger() -> logging.Logger:
    logger = logging.getLogger("project_atlas")
    logger.setLevel(getattr(logging, settings.LOG_LEVEL.upper(), logging.INFO))
    logger.handlers = []

    handler = logging.StreamHandler(sys.stdout)
    if settings.ENABLE_JSON_LOGS:
        handler.setFormatter(JsonFormatter())
    else:
        handler.setFormatter(logging.Formatter("[%(asctime)s] [%(levelname)s] %(name)s: %(message)s"))

    logger.addHandler(handler)
    return logger


logger = setup_observability_logger()


def log_event(
    event_name: str,
    client_id: str | None = None,
    latency_ms: int | None = None,
    router_decision: str | None = None,
    tokens: int = 0,
    cost_usd: float = 0.0,
    escalated: bool = False,
    confidence: float | None = None,
    security_alert: str | None = None,
    **kwargs: Any
):
    """Log structured telemetry event without leaking sensitive credentials."""
    extra = {
        "event": event_name,
        "client_id": client_id,
        "latency_ms": latency_ms,
        "router_decision": router_decision,
        "tokens_used": tokens,
        "cost_usd": cost_usd,
        "escalated": escalated,
        "confidence": confidence,
        "security_alert": security_alert
    }
    # Sanitize any kwargs to avoid leaking PII
    for k, v in kwargs.items():
        if "email" not in k and "password" not in k and "secret" not in k:
            extra[k] = v

    logger.info(f"Event: {event_name}", extra={"extra_fields": extra})
