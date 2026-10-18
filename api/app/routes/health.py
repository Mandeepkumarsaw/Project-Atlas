from fastapi import APIRouter, Response, status
from sqlalchemy import text
from app.schemas.health import HealthResponse
from app.config import settings
from app.services.db import app_engine
from app.services.vector_store import get_qdrant_client
from app.observability.logger import logger

router = APIRouter(tags=["Health"])


@router.get("/health", response_model=HealthResponse)
def get_health(response: Response):
    # Check MySQL / SQLite
    mysql_status = "healthy"
    try:
        with app_engine.connect() as conn:
            conn.execute(text("SELECT 1"))
    except Exception as e:
        logger.warning(f"Database health check failed: {e}")
        mysql_status = "unhealthy"

    # Check Qdrant
    qdrant_status = "healthy"
    try:
        client = get_qdrant_client()
        client.get_collections()
    except Exception as e:
        logger.warning(f"Vector database health check failed: {e}")
        qdrant_status = "unhealthy"

    is_healthy = (mysql_status == "healthy" and qdrant_status == "healthy")
    if not is_healthy:
        response.status_code = status.HTTP_503_SERVICE_UNAVAILABLE

    return HealthResponse(
        status="ok" if is_healthy else "degraded",
        mysql=mysql_status,
        vector_db=qdrant_status,
        model=settings.LLM_MODEL,
        environment=settings.ENVIRONMENT,
        version="1.0.0"
    )


