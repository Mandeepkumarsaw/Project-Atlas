from datetime import datetime
from pydantic import BaseModel, Field


class EscalationItem(BaseModel):
    ticket_id: str
    conversation_id: str
    client_id: str
    question: str
    reason: str
    status: str
    analyst_assigned: str | None = None
    created_at: datetime | str


class EscalationListResponse(BaseModel):
    total: int = Field(..., description="Count of returned escalations")
    client_id: str | None = Field(default=None, description="Client filter applied")
    escalations: list[EscalationItem] = Field(default_factory=list)
