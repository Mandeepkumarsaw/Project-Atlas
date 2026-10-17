import time
import uuid
from fastapi import APIRouter, Depends, HTTPException, Header
from app.schemas.chat import ChatRequest, ChatResponse, Citation, DerivedValue
from app.security.auth import verify_api_key, validate_client_context
from app.agents.graph import run_agent_workflow
from app.services.db import record_request_log
from app.observability.logger import log_event

router = APIRouter(tags=["Chat"])


@router.post("/chat", response_model=ChatResponse)
def execute_chat(
    req: ChatRequest,
    _auth: str = Depends(verify_api_key)
):
    start_time = time.time()
    req_id = f"req-{uuid.uuid4().hex[:8]}"

    # Validate client exists in database records
    # Validates that client context is trusted server-side, not merely trusted from prompt
    validate_client_context(req.client_id)

    try:
        result = run_agent_workflow(
            client_id=req.client_id,
            conversation_id=req.conversation_id,
            question=req.question
        )

        latency_ms = int((time.time() - start_time) * 1000)
        # Approximate token usage (prompt + answer chars / 4)
        tokens_est = int((len(req.question) + len(result.get("answer", ""))) / 4)
        cost_est = round(tokens_est * 0.0000015, 6)

        record_request_log(
            request_id=req_id,
            client_id=req.client_id,
            endpoint="/chat",
            router_decision=result.get("router_decision"),
            latency_ms=latency_ms,
            tokens_used=tokens_est,
            estimated_cost_usd=cost_est,
            status_code=200
        )

        log_event(
            event_name="chat_request_completed",
            client_id=req.client_id,
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

    except Exception as e:
        latency_ms = int((time.time() - start_time) * 1000)
        record_request_log(
            request_id=req_id,
            client_id=req.client_id,
            endpoint="/chat",
            router_decision="error",
            latency_ms=latency_ms,
            tokens_used=0,
            estimated_cost_usd=0.0,
            status_code=500
        )
        raise HTTPException(status_code=500, detail=f"Internal agent processing error: {str(e)}")
