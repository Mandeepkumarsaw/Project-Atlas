from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session
from app.schemas.escalation import EscalationListResponse, EscalationItem
from app.security.auth import verify_api_key
from app.services.db import get_db, Escalation

router = APIRouter(tags=["Escalations"])


@router.get("/escalations", response_model=EscalationListResponse)
def get_escalations(
    client_id: str | None = Query(default=None, description="Optional client ID filter"),
    db: Session = Depends(get_db),
    _auth: str = Depends(verify_api_key)
):
    query = db.query(Escalation)
    if client_id:
        query = query.filter(Escalation.client_id == client_id)

    records = query.order_by(Escalation.created_at.desc()).all()
    items = [
        EscalationItem(
            ticket_id=r.ticket_id,
            conversation_id=r.conversation_id,
            client_id=r.client_id,
            question=r.question,
            reason=r.reason,
            status=r.status,
            analyst_assigned=r.analyst_assigned,
            created_at=r.created_at.isoformat() if r.created_at else ""
        )
        for r in records
    ]

    return EscalationListResponse(
        total=len(items),
        client_id=client_id,
        escalations=items
    )
