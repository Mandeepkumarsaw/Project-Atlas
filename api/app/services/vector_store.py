import os
from typing import Any
from qdrant_client import QdrantClient
from qdrant_client.models import (
    Distance, VectorParams, PointStruct, Filter, FieldCondition, MatchAny, MatchValue
)
from app.config import settings
from app.services.embeddings import compute_embedding, EMBEDDING_DIM

_client: QdrantClient | None = None


def get_qdrant_client() -> QdrantClient:
    global _client
    if _client is not None:
        return _client

    # Attempt connection to configured Qdrant server
    try:
        if settings.QDRANT_URL:
            client = QdrantClient(url=settings.QDRANT_URL, timeout=3.0)
        else:
            client = QdrantClient(host=settings.QDRANT_HOST, port=settings.QDRANT_PORT, timeout=3.0)
        # Test connection
        client.get_collections()
        _client = client
        return _client
    except Exception:
        # Fallback to local in-memory Qdrant client for standalone / testing execution
        client = QdrantClient(":memory:")
        _client = client
        return _client


def init_vector_collection(recreate: bool = False):
    """Ensure the target collection exists in Qdrant."""
    client = get_qdrant_client()
    col_name = settings.QDRANT_COLLECTION

    collections = client.get_collections().collections
    exists = any(c.name == col_name for c in collections)

    if exists and recreate:
        client.delete_collection(collection_name=col_name)
        exists = False

    if not exists:
        client.create_collection(
            collection_name=col_name,
            vectors_config=VectorParams(size=EMBEDDING_DIM, distance=Distance.COSINE),
        )


def upsert_chunks(chunks: list[dict[str, Any]]):
    """
    Upsert document chunks into Qdrant.
    Each chunk dict must have:
      - id (int or uuid)
      - vector (list[float])
      - payload (dict with report_id, page, chunk_id, text, etc.)
    """
    client = get_qdrant_client()
    col_name = settings.QDRANT_COLLECTION

    points = [
        PointStruct(
            id=c["id"],
            vector=c["vector"],
            payload=c["payload"]
        )
        for c in chunks
    ]
    client.upsert(collection_name=col_name, points=points)


def search_chunks(
    query: str,
    allowed_report_ids: set[str],
    top_k: int = 5,
    score_threshold: float = 0.20
) -> list[dict[str, Any]]:
    """
    Retrieve chunks matching query text, strictly filtering by allowed_report_ids.
    Security Boundary: If allowed_report_ids is empty, returns empty list immediately!
    """
    if not allowed_report_ids:
        return []

    client = get_qdrant_client()
    col_name = settings.QDRANT_COLLECTION

    # Ensure collection exists
    init_vector_collection(recreate=False)

    query_vector = compute_embedding(query)

    # Construct Qdrant filter on report_id
    qdrant_filter = Filter(
        must=[
            FieldCondition(
                key="report_id",
                match=MatchAny(any=list(allowed_report_ids))
            )
        ]
    )

    try:
        search_results = client.search(
            collection_name=col_name,
            query_vector=query_vector,
            query_filter=qdrant_filter,
            limit=top_k,
            score_threshold=score_threshold
        )
    except Exception as e:
        # If collection is empty or error
        return []

    results = []
    for hit in search_results:
        # Double check entitlement allowlist locally as defense in depth
        if hit.payload and hit.payload.get("report_id") in allowed_report_ids:
            results.append({
                "score": hit.score,
                "report_id": hit.payload.get("report_id"),
                "chunk_id": hit.payload.get("chunk_id"),
                "page": hit.payload.get("page"),
                "sector": hit.payload.get("sector"),
                "region": hit.payload.get("region"),
                "year": hit.payload.get("year"),
                "access_level": hit.payload.get("access_level"),
                "text": hit.payload.get("text", "")
            })

    return results
