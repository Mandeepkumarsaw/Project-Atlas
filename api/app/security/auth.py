from dataclasses import dataclass
from typing import Optional
from fastapi import Header, HTTPException, status, Depends
from sqlalchemy.orm import Session
from app.config import settings
from app.services.db import SessionLocal, Client
from app.observability.logger import logger


@dataclass
class AuthenticatedIdentity:
    token: str
    role: str  # "admin" or "client"
    client_id: Optional[str]  # e.g. "C-01", "C-02", or None for admin
    is_admin: bool = False


def extract_credential(
    x_api_key: str | None = Header(default=None, alias="X-API-Key"),
    authorization: str | None = Header(default=None, alias="Authorization"),
) -> str:
    """Extract credential from either X-API-Key or Authorization Bearer header."""
    if x_api_key and x_api_key.strip():
        return x_api_key.strip()
    if authorization and authorization.strip():
        parts = authorization.strip().split()
        if len(parts) == 2 and parts[0].lower() in ("bearer", "apikey"):
            return parts[1].strip()
        return authorization.strip()
    raise HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Missing authentication credential. Provide 'X-API-Key' or 'Authorization: Bearer <key>'.",
        headers={"WWW-Authenticate": "ApiKey"},
    )


def get_authenticated_identity(
    credential: str = Depends(extract_credential)
) -> AuthenticatedIdentity:
    """
    Authenticate credential and bind to a verified client or admin identity.
    Fails closed with HTTP 401 if token is unapproved or unrecognized.
    """
    # 1. Administrative / Service credentials
    if credential in (settings.ADMIN_API_KEY, settings.API_KEY):
        return AuthenticatedIdentity(
            token=credential,
            role="admin",
            client_id=None,
            is_admin=True
        )

    # 2. Client-scoped credentials
    client_mapping = settings.get_client_keys()
    if credential in client_mapping:
        scoped_client_id = client_mapping[credential]
        return AuthenticatedIdentity(
            token=credential,
            role="client",
            client_id=scoped_client_id,
            is_admin=False
        )

    # 3. Fail closed on unrecognized credential
    logger.warning("Authentication failure: unrecognized API key credential provided.")
    raise HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Invalid authentication credential.",
        headers={"WWW-Authenticate": "ApiKey"},
    )


def enforce_client_access(auth: AuthenticatedIdentity, requested_client_id: str | None) -> str | None:
    """
    Enforce tenant boundary:
    - If caller is a scoped client identity, they CANNOT specify or impersonate a different client_id.
    - If caller is a scoped client identity and client_id is None, it defaults to their bound client_id.
    - If caller is an admin, they may specify any registered client_id, or None for unscoped views.
    """
    if not auth.is_admin:
        # Caller is client-scoped
        if requested_client_id and requested_client_id.strip() and requested_client_id.strip() != auth.client_id:
            logger.warning(
                f"Cross-client access attempt blocked: Authenticated client '{auth.client_id}' "
                f"attempted to impersonate/access data for client '{requested_client_id}'"
            )
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail=f"Forbidden: Client '{auth.client_id}' is not authorized to act as or access data for client '{requested_client_id}'."
            )
        return auth.client_id

    # Caller is admin
    if requested_client_id and requested_client_id.strip():
        req_clean = requested_client_id.strip()
        validate_client_context(req_clean)
        return req_clean
    return None


def require_admin_role(auth: AuthenticatedIdentity = Depends(get_authenticated_identity)) -> AuthenticatedIdentity:
    """Enforce administrative privilege for privileged endpoints (e.g. Ingestion)."""
    if not auth.is_admin:
        logger.warning(
            f"Unauthorized administrative access attempt by client identity '{auth.client_id}'."
        )
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Forbidden: Administrative privileges are required to perform this action."
        )
    return auth


def verify_api_key(auth: AuthenticatedIdentity = Depends(get_authenticated_identity)) -> str:
    """Backwards-compatible API key verifier returning the verified token string."""
    return auth.token


def validate_client_context(client_id: str, db: Session | None = None) -> Client:
    """Verify that client_id corresponds to a valid registered client in the database."""
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

