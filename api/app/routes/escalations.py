from fastapi import APIRouter, Depends, Query, HTTPException
from sqlalchemy.orm import Session
from app.schemas.escalation import EscalationListResponse, EscalationItem
from app.security.auth import get_authenticated_identity, enforce_client_access, AuthenticatedIdentity
from app.services.db import get_db, Escalation

router = APIRouter(tags=["Escalations"])


@router.get("/escalations", response_model=EscalationListResponse)
def get_escalations(
    client_id: str | None = Query(default=None, description="Client ID filter (required/bound for non-admin callers)"),
    db: Session = Depends(get_db),
    auth: AuthenticatedIdentity = Depends(get_authenticated_identity)
):
    """
    Retrieve analyst escalation tickets.
    Enforces tenant scoping: Non-admin clients only receive tickets belonging to their authenticated identity.
    Attempts to enumerate or query another client's escalations are rejected with HTTP 403 Forbidden.
    """
    # Enforce tenant boundary (raises 403 if client-scoped caller attempts cross-client access)
    effective_client_id = enforce_client_access(auth, client_id)

    query = db.query(Escalation)
    if effective_client_id:
        query = query.filter(Escalation.client_id == effective_client_id)
    elif not auth.is_admin:
        # Defense-in-depth: non-admin without effective client ID is rejected
        raise HTTPException(
            status_code=403,
            detail="Forbidden: Client identity must be bound to retrieve escalations."
        )

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
        client_id=effective_client_id,
        escalations=items
    )

