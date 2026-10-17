from fastapi import APIRouter, Depends, HTTPException
from app.schemas.ingest import IngestRequest, IngestResponse
from app.security.auth import verify_api_key
from app.ingestion.cli import run_ingestion

router = APIRouter(tags=["Ingestion"])


@router.post("/ingest", response_model=IngestResponse)
def trigger_ingestion(
    request: IngestRequest,
    _auth: str = Depends(verify_api_key)
):
    try:
        stats = run_ingestion(
            reports_dir=request.reports_dir,
            force_reindex=request.force_reindex
        )
        return IngestResponse(**stats)
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Ingestion failed: {str(e)}")
