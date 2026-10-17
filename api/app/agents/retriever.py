from typing import Any
from app.services.vector_store import search_chunks


def retrieve_authorized_evidence(
    question: str,
    allowed_report_ids: set[str],
    top_k: int = 5
) -> list[dict[str, Any]]:
    """
    Retrieve document chunks from Qdrant strictly enforcing client's report entitlement allowlist.
    """
    if not allowed_report_ids:
        return []

    chunks = search_chunks(
        query=question,
        allowed_report_ids=allowed_report_ids,
        top_k=top_k,
        score_threshold=0.15
    )

    return chunks
