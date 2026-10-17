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

# CORS configuration for PHP portal and frontend clients
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Register routes
app.include_router(health_router)
app.include_router(ingest_router)
app.include_router(chat_router)
app.include_router(escalations_router)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("app.main:app", host=settings.API_HOST, port=settings.API_PORT, reload=True)
