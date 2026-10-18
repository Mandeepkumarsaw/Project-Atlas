from pydantic import BaseModel, Field


class IngestRequest(BaseModel):
    reports_dir: str = Field(default="reports", description="Path to reports directory within project root")
    force_reindex: bool = Field(default=False, description="Whether to drop and recreate collection")


class IngestResponse(BaseModel):
    indexed_reports: int = Field(..., description="Number of reports successfully indexed")
    report_ids: list[str] = Field(default_factory=list, description="IDs of successfully indexed reports")
    total_chunks: int = Field(..., description="Total document chunks generated and upserted")
    detected_injections: int = Field(..., description="Number of injection attempts detected and quarantined")
    skipped_files: list[str] = Field(default_factory=list, description="Files skipped during ingestion")
    errors: list[str] = Field(default_factory=list, description="Validation or parsing errors encountered")
    duration_ms: int = Field(..., description="Total execution time in milliseconds")
