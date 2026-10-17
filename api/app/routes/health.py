from fastapi import APIRouter
from sqlalchemy import text
from app.schemas.health import HealthResponse
from app.config import settings
from app.services.db import app_engine
from app.services.vector_store import get_qdrant_client

router = APIRouter(tags=["Health"])


@router.get("/health", response_model=HealthResponse)
def get_health():
    # Check MySQL / SQLite
    mysql_status = "healthy"
    try:
        with app_engine.connect() as conn:
            conn.execute(text("SELECT 1"))
    except Exception as e:
        mysql_status = f"unhealthy: {str(e)[:50]}"

    # Check Qdrant
    qdrant_status = "healthy"
    try:
        client = get_qdrant_client()
        client.get_collections()
    except Exception as e:
        qdrant_status = f"unhealthy: {str(e)[:50]}"

    return HealthResponse(
        status="ok" if mysql_status == "healthy" and qdrant_status == "healthy" else "degraded",
        mysql=mysql_status,
        vector_db=qdrant_status,
        model=settings.LLM_MODEL,
        environment=settings.ENVIRONMENT,
        version="1.0.0"
    )
