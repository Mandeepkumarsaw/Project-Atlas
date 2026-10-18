from fastapi import Header, HTTPException, status
from sqlalchemy.orm import Session
from app.config import settings
from app.services.db import SessionLocal, Client


def verify_api_key(x_api_key: str = Header(default=None, alias="X-API-Key")) -> str:
    """Validate server-to-server API key."""
    if not x_api_key or x_api_key != settings.API_KEY:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid or missing X-API-Key header",
            headers={"WWW-Authenticate": "ApiKey"},
        )
    return x_api_key


def validate_client_context(client_id: str, db: Session | None = None) -> Client:
    """Verify that client_id corresponds to a valid registered client in MySQL."""
    if db is None:
        with SessionLocal() as session:
            client = session.query(Client).filter(Client.client_id == client_id).first()
    else:
        client = db.query(Client).filter(Client.client_id == client_id).first()

    if not client:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=f"Client '{client_id}' is not registered or entitled in the system",
        )
    return client
