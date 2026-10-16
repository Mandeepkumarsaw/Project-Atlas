import hashlib
import numpy as np
from app.config import settings

_model = None
EMBEDDING_DIM = 384


def get_embedding_model():
    global _model
    if _model is not None:
        return _model
    try:
        from sentence_transformers import SentenceTransformer
        _model = SentenceTransformer(settings.EMBEDDING_MODEL)
        return _model
    except Exception as e:
        # Fallback to deterministic pseudo-dense embedding for offline / lightweight environments
        return None


def compute_embedding(text: str) -> list[float]:
    """Compute dense embedding vector of dimension 384."""
    model = get_embedding_model()
    if model is not None:
        try:
            vec = model.encode(text, normalize_embeddings=True)
            return vec.tolist()
        except Exception:
            pass

    # Deterministic fallback embedding generator (384 dimensions)
    # Generates a normalized unit vector based on hashing tokens
    vector = np.zeros(EMBEDDING_DIM, dtype=np.float32)
    tokens = text.lower().split()
    for token in tokens:
        h = int(hashlib.md5(token.encode("utf-8")).hexdigest(), 16)
        idx = h % EMBEDDING_DIM
        sign = 1.0 if (h >> 8) % 2 == 0 else -1.0
        vector[idx] += sign
    
    norm = np.linalg.norm(vector)
    if norm > 0:
        vector = vector / norm
    else:
        vector[0] = 1.0
    return vector.tolist()


def compute_embeddings_batch(texts: list[str]) -> list[list[float]]:
    """Compute embeddings for a batch of strings."""
    model = get_embedding_model()
    if model is not None:
        try:
            vectors = model.encode(texts, normalize_embeddings=True)
            return [v.tolist() for v in vectors]
        except Exception:
            pass
    return [compute_embedding(t) for t in texts]
