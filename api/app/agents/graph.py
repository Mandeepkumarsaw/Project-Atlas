import time
from typing import TypedDict, Any
from langgraph.graph import StateGraph, END
from app.agents.router import classify_question
from app.agents.retriever import retrieve_authorized_evidence
from app.agents.calculator import calculate_market_projection
from app.agents.sql_tool import run_sector_aggregate_query
from app.agents.critic import evaluate_response_grounding
from app.agents.writer import compose_grounded_answer
from app.security.entitlements import get_authorized_reports
from app.security.injection_detector import scan_for_injection
from app.services.db import create_escalation_ticket, record_chat_message
from app.schemas.chat import Citation, DerivedValue


class AgentState(TypedDict):
    client_id: str
    conversation_id: str
    question: str
    allowed_report_ids: set[str]
    router_decision: str
    evidence_chunks: list[dict[str, Any]]
    calc_result: tuple[bool, dict[str, Any] | None, str] | None
    sql_result: tuple[bool, Any, str] | None
    answer: str
    citations: list[Citation]
    confidence: float
    conflicts: list[str]
    derived_values: list[DerivedValue]
    escalated: bool
    ticket_id: str | None
    upgrade_guidance: str | None
    error: str | None


def validate_request_node(state: AgentState) -> dict:
    client_id = state["client_id"]
    question = state["question"]

    # 1. Check prompt injection
    is_inj, pattern = scan_for_injection(question, client_id=client_id, source="user_prompt")
    if is_inj:
        return {
            "router_decision": "unsafe",
            "confidence": 1.0,
            "escalated": False
        }

    # 2. Load server-side client entitlements from MySQL
    allowed = get_authorized_reports(client_id)
    return {
        "allowed_report_ids": allowed,
        "escalated": False,
        "ticket_id": None
    }


def router_node(state: AgentState) -> dict:
    if state.get("router_decision") == "unsafe":
        return {}
    decision = classify_question(state["question"])
    return {"router_decision": decision}


def retriever_node(state: AgentState) -> dict:
    allowed = state.get("allowed_report_ids", set())
    chunks = retrieve_authorized_evidence(state["question"], allowed)
    return {"evidence_chunks": chunks}


def calculator_node(state: AgentState) -> dict:
    calc_res = calculate_market_projection(state["question"])
    return {"calc_result": calc_res}


def sql_tool_node(state: AgentState) -> dict:
    sql_res = run_sector_aggregate_query(state["question"], client_id=state["client_id"])
    return {"sql_result": sql_res}


def refusal_node(state: AgentState) -> dict:
    answer, citations, derived_values = compose_grounded_answer(
        router_decision=state["router_decision"],
        question=state["question"],
        client_id=state["client_id"],
        evidence_chunks=[],
        allowed_report_ids=state.get("allowed_report_ids", set())
    )
    return {
        "answer": answer,
        "citations": citations,
        "derived_values": derived_values,
        "confidence": 1.0,
        "conflicts": [],
        "escalated": False
    }


def critic_node(state: AgentState) -> dict:
    router_decision = state.get("router_decision", "factual")
    question = state["question"]
    allowed = state.get("allowed_report_ids", set())
    evidence_chunks = state.get("evidence_chunks", [])
    calc_result = state.get("calc_result")
    sql_result = state.get("sql_result")

    # Preliminary answer to fact-check
    draft_answer, draft_citations, draft_derived = compose_grounded_answer(
        router_decision=router_decision,
        question=question,
        client_id=state["client_id"],
        evidence_chunks=evidence_chunks,
        allowed_report_ids=allowed,
        calc_result=calc_result,
        sql_result=sql_result
    )

    # Check if calculation requires escalation (e.g., 2035 out of bounds)
    if calc_result and not calc_result[0]:
        return {
            "confidence": 0.45,
            "conflicts": [],
            "escalated": True,
            "error": calc_result[2]
        }

    confidence, conflicts, should_escalate, reason = evaluate_response_grounding(
        question=question,
        draft_answer=draft_answer,
        citations=draft_citations,
        evidence_chunks=evidence_chunks,
        allowed_report_ids=allowed
    )

    return {
        "confidence": confidence,
        "conflicts": conflicts,
        "escalated": should_escalate,
        "error": reason if should_escalate else None
    }


def escalation_node(state: AgentState) -> dict:
    ticket_id = create_escalation_ticket(
        client_id=state["client_id"],
        conversation_id=state["conversation_id"],
        question=state["question"],
        reason=state.get("error") or "Low confidence / source conflict requires analyst evaluation"
    )
    return {"ticket_id": ticket_id, "escalated": True}


def writer_node(state: AgentState) -> dict:
    answer, citations, derived_values = compose_grounded_answer(
        router_decision=state.get("router_decision", "factual"),
        question=state["question"],
        client_id=state["client_id"],
        evidence_chunks=state.get("evidence_chunks", []),
        allowed_report_ids=state.get("allowed_report_ids", set()),
        calc_result=state.get("calc_result"),
        sql_result=state.get("sql_result"),
        conflicts=state.get("conflicts", []),
        escalation_ticket_id=state.get("ticket_id"),
        upgrade_guidance=state.get("upgrade_guidance")
    )
    return {
        "answer": answer,
        "citations": citations,
        "derived_values": derived_values
    }


# Routing logic for conditional edges
def route_question(state: AgentState) -> str:
    decision = state.get("router_decision")
    if decision in ["unsafe", "out_of_scope"]:
        return "refusal"
    elif decision == "sql_aggregate":
        return "sql_tool"
    elif decision == "calculation":
        return "calculator"
    else:
        return "retriever"


def route_after_critic(state: AgentState) -> str:
    if state.get("escalated"):
        return "escalation"
    return "writer"


def build_workflow():
    workflow = StateGraph(AgentState)

    workflow.add_node("validate_request", validate_request_node)
    workflow.add_node("router", router_node)
    workflow.add_node("retriever", retriever_node)
    workflow.add_node("calculator", calculator_node)
    workflow.add_node("sql_tool", sql_tool_node)
    workflow.add_node("critic", critic_node)
    workflow.add_node("escalation", escalation_node)
    workflow.add_node("writer", writer_node)
    workflow.add_node("refusal", refusal_node)

    workflow.set_entry_point("validate_request")
    workflow.add_edge("validate_request", "router")

    workflow.add_conditional_edges(
        "router",
        route_question,
        {
            "refusal": "refusal",
            "sql_tool": "sql_tool",
            "calculator": "calculator",
            "retriever": "retriever"
        }
    )

    workflow.add_edge("retriever", "critic")
    workflow.add_edge("calculator", "critic")
    workflow.add_edge("sql_tool", "critic")

    workflow.add_conditional_edges(
        "critic",
        route_after_critic,
        {
            "escalation": "escalation",
            "writer": "writer"
        }
    )

    workflow.add_edge("escalation", "writer")
    workflow.add_edge("writer", END)
    workflow.add_edge("refusal", END)

    return workflow.compile()


app_workflow = build_workflow()


def run_agent_workflow(client_id: str, conversation_id: str, question: str) -> dict[str, Any]:
    """Execute end-to-end agent workflow."""
    initial_state: AgentState = {
        "client_id": client_id,
        "conversation_id": conversation_id,
        "question": question,
        "allowed_report_ids": set(),
        "router_decision": "factual",
        "evidence_chunks": [],
        "calc_result": None,
        "sql_result": None,
        "answer": "",
        "citations": [],
        "confidence": 0.0,
        "conflicts": [],
        "derived_values": [],
        "escalated": False,
        "ticket_id": None,
        "upgrade_guidance": None,
        "error": None
    }

    result = app_workflow.invoke(initial_state)

    # Persist chat message in MySQL
    record_chat_message(
        conversation_id=conversation_id,
        client_id=client_id,
        role="assistant",
        content=result.get("answer", ""),
        citations=[c.dict() for c in result.get("citations", [])],
        confidence=result.get("confidence", 0.0),
        conflicts=result.get("conflicts", []),
        derived_values=[d.dict() for d in result.get("derived_values", [])],
        status="escalated" if result.get("escalated") else "success"
    )

    return result
