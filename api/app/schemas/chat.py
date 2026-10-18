from typing import Any
from pydantic import BaseModel, Field


class Citation(BaseModel):
    report_id: str = Field(..., description="Unique report identifier e.g. R-101")
    page: int = Field(..., description="Exact page or section marker cited")
    claim: str = Field(..., description="Specific claim supported by cited text")


class DerivedValue(BaseModel):
    metric: str = Field(..., description="Name of derived metric")
    formula: str = Field(..., description="Mathematical formula used")
    inputs: dict[str, Any] = Field(default_factory=dict, description="Variables and constants used")
    assumptions: list[str] = Field(default_factory=list, description="Explicit assumptions stated")
    result: float | str = Field(..., description="Calculated numerical or string value")


class ChatRequest(BaseModel):
    client_id: str = Field(
        ...,
        min_length=2,
        max_length=32,
        pattern=r"^[A-Za-z0-9_-]+$",
        description="Authenticated client identifier, e.g. C-01"
    )
    conversation_id: str = Field(
        default="conv-default",
        min_length=2,
        max_length=64,
        pattern=r"^[A-Za-z0-9_-]+$",
        description="Conversation thread identifier"
    )
    question: str = Field(..., min_length=2, max_length=1000, description="User's query")



class ChatResponse(BaseModel):
    answer: str = Field(..., description="Evidence-backed natural language response")
    citations: list[Citation] = Field(default_factory=list, description="Grounded citations")
    confidence: float = Field(..., ge=0.0, le=1.0, description="Routing and grounding confidence score")
    conflicts: list[str] = Field(default_factory=list, description="Identified report discrepancies")
    derived_values: list[DerivedValue] = Field(default_factory=list, description="Deterministic calculations")
    escalated: bool = Field(default=False, description="Whether inquiry was escalated to research analyst")
    ticket_id: str | None = Field(default=None, description="Escalation ticket ID if escalated")
