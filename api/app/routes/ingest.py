import logging
from fastapi import APIRouter, Depends, HTTPException, status
from app.schemas.ingest import IngestRequest, IngestResponse
from app.security.auth import require_admin_role, AuthenticatedIdentity
from app.ingestion.cli import run_ingestion

logger = logging.getLogger("atlas.ingest")
router = APIRouter(tags=["Ingestion"])


@router.post("/ingest", response_model=IngestResponse)
def trigger_ingestion(
    request: IngestRequest,
    _auth: AuthenticatedIdentity = Depends(require_admin_role)
):
    """
    Trigger ingestion of market research reports into the vector database.
    Requires server-to-server API key. Enforces path traversal and directory containment protections.
    """
    try:
        stats = run_ingestion(
            reports_dir=request.reports_dir,
            force_reindex=request.force_reindex
        )
        return IngestResponse(**stats)
    except ValueError as ve:
        # Path traversal or directory boundary validation failure
        logger.warning(f"Ingestion request rejected due to validation failure: {ve}")
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Validation Error: {str(ve)}"
        )
    except FileNotFoundError as fe:
        logger.warning(f"Ingestion directory not found: {fe}")
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=f"Resource Not Found: {str(fe)}"
        )
    except Exception as e:
        logger.error(f"Unexpected internal ingestion failure: {e}", exc_info=True)
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Internal ingestion service failure. Diagnostic details logged to server telemetry."
        )
