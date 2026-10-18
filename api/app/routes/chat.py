import time
import uuid
from fastapi import APIRouter, Depends, HTTPException, Header
from app.schemas.chat import ChatRequest, ChatResponse, Citation, DerivedValue
from app.security.auth import get_authenticated_identity, enforce_client_access, validate_client_context, AuthenticatedIdentity
from app.agents.graph import run_agent_workflow
from app.services.db import record_request_log
from app.observability.logger import log_event, logger

router = APIRouter(tags=["Chat"])


@router.post("/chat", response_model=ChatResponse)
def execute_chat(
    req: ChatRequest,
    auth: AuthenticatedIdentity = Depends(get_authenticated_identity)
):
    start_time = time.time()
    req_id = f"req-{uuid.uuid4().hex[:8]}"

    # Enforce tenant isolation & identity binding:
    # 1. Scoped client keys cannot impersonate another client (raises HTTP 403)
    # 2. Authenticated identity is verified server-side
    effective_client_id = enforce_client_access(auth, req.client_id)
    if not effective_client_id:
        raise HTTPException(status_code=400, detail="Missing required client_id in chat request.")

    # Validate that client exists in database records
    validate_client_context(effective_client_id)

    try:
        result = run_agent_workflow(
            client_id=effective_client_id,
            conversation_id=req.conversation_id,
            question=req.question
        )

        latency_ms = int((time.time() - start_time) * 1000)
        # Approximate token usage (prompt + answer chars / 4)
        tokens_est = int((len(req.question) + len(result.get("answer", ""))) / 4)
        cost_est = round(tokens_est * 0.0000015, 6)

        record_request_log(
            request_id=req_id,
            client_id=effective_client_id,
            endpoint="/chat",
            router_decision=result.get("router_decision"),
            latency_ms=latency_ms,
            tokens_used=tokens_est,
            estimated_cost_usd=cost_est,
            status_code=200
        )

        log_event(
            event_name="chat_request_completed",
            client_id=effective_client_id,
            latency_ms=latency_ms,
            router_decision=result.get("router_decision"),
            tokens=tokens_est,
            cost_usd=cost_est,
            escalated=result.get("escalated", False),
            confidence=result.get("confidence", 0.0)
        )

        return ChatResponse(
            answer=result.get("answer", ""),
            citations=result.get("citations", []),
            confidence=result.get("confidence", 0.0),
            conflicts=result.get("conflicts", []),
            derived_values=result.get("derived_values", []),
            escalated=result.get("escalated", False),
            ticket_id=result.get("ticket_id")
        )

    except HTTPException:
        # Re-raise explicit HTTP exceptions (400, 401, 403, 404, etc.) without wrapping in 500
        raise
    except Exception as e:
        # Sanitized error response (ATLAS-SEC-001): Never leak raw exceptions, traces, or paths to client
        latency_ms = int((time.time() - start_time) * 1000)
        logger.error(f"Internal chat processing failure [request_id={req_id}]: {e}", exc_info=True)
        record_request_log(
            request_id=req_id,
            client_id=effective_client_id,
            endpoint="/chat",
            router_decision="error",
            latency_ms=latency_ms,
            tokens_used=0,
            estimated_cost_usd=0.0,
            status_code=500
        )
        raise HTTPException(
            status_code=500,
            detail=f"An unexpected internal error occurred. Please contact support with Request ID: {req_id}"
        )

