from contextlib import asynccontextmanager
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.config import settings
from app.services.db import init_database_tables
from app.services.vector_store import init_vector_collection
from app.routes.health import router as health_router
from app.routes.ingest import router as ingest_router
from app.routes.chat import router as chat_router
from app.routes.escalations import router as escalations_router
from app.observability.logger import logger


@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Ensure database tables and vector collection exist
    logger.info("Initializing Project Atlas application services...")
    try:
        init_database_tables()
        init_vector_collection()
        logger.info("Database and vector collection successfully initialized.")
    except Exception as e:
        logger.warning(f"Startup initialization notice: {e}")
    yield
    # Shutdown
    logger.info("Project Atlas service shutting down.")


app = FastAPI(
    title="Project Atlas: AI-Powered Market Research Report Copilot",
    description="Cognitive Market Research and Consulting - Entitlement-aware RAG, multi-agent reasoning, deterministic calculations, and escalation.",
    version="1.0.0",
    lifespan=lifespan
)

# CORS configuration: Restricted to explicit allowed origins, rejecting wildcard with credentials
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.get_allowed_origins(),
    allow_credentials=True,
    allow_methods=["GET", "POST", "OPTIONS"],
    allow_headers=["Authorization", "Content-Type", "X-API-Key", "X-Request-ID"],
)

from fastapi import Request
from fastapi.responses import JSONResponse
import uuid

@app.exception_handler(Exception)
async def unhandled_exception_handler(request: Request, exc: Exception):
    """Global exception handler ensuring unexpected errors never leak internal stack traces (ATLAS-SEC-001)."""
    req_id = request.headers.get("X-Request-ID") or f"req-{uuid.uuid4().hex[:8]}"
    logger.error(
        f"Unhandled server exception [request_id={req_id}] on {request.method} {request.url.path}: {exc}",
        exc_info=True
    )
    return JSONResponse(
        status_code=500,
        content={
            "detail": f"An unexpected internal server error occurred. Request ID: {req_id}",
            "request_id": req_id
        }
    )

from app.routes.ui import router as ui_router

# Register routes
app.include_router(ui_router)
app.include_router(health_router)
app.include_router(ingest_router)
app.include_router(chat_router)
app.include_router(escalations_router)



if __name__ == "__main__":
    import uvicorn
    uvicorn.run("app.main:app", host=settings.API_HOST, port=settings.API_PORT, reload=True)
