from pydantic import BaseModel, Field


class IngestRequest(BaseModel):
    reports_dir: str = Field(default="reports", description="Path to reports directory")
    force_reindex: bool = Field(default=False, description="Whether to drop and recreate collection")


class IngestResponse(BaseModel):
    indexed_reports: int = Field(..., description="Number of reports indexed")
    total_chunks: int = Field(..., description="Total document chunks generated")
    detected_injections: int = Field(..., description="Number of injection attempts detected and quarantined")
    duration_ms: int = Field(..., description="Total execution time in milliseconds")
