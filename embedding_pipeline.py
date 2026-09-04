"""
Embedding + semantic matching pipeline for BIS standards.
No custom model training required — uses a pretrained sentence transformer.
"""

from sentence_transformers import SentenceTransformer
import faiss
import numpy as np
import json

MODEL_NAME = "all-MiniLM-L6-v2"  
model = SentenceTransformer(MODEL_NAME)


def build_index(standards: list[dict]):
    """
    standards: list of {"code": "IS 456", "title": "...", "scope": "..."}
    Returns a FAISS index + the list (so we can map index positions back to records).
    """
    texts = [f"{s['title']} {s['scope']}" for s in standards]
    embeddings = model.encode(texts, normalize_embeddings=True, show_progress_bar=True)
    embeddings = np.array(embeddings).astype("float32")

    dim = embeddings.shape[1]
    index = faiss.IndexFlatIP(dim)  # inner product on normalized vecs = cosine sim
    index.add(embeddings)
    return index, standards


def search(query: str, index, standards: list[dict], top_k: int = 5):
    q_emb = model.encode([query], normalize_embeddings=True).astype("float32")
    scores, ids = index.search(q_emb, top_k)

    results = []
    for score, idx in zip(scores[0], ids[0]):
        if idx == -1:
            continue
        record = standards[idx]
        results.append({
            "code": record["code"],
            "title": record["title"],
            "semantic_score": float(score),
        })
    return results


def rank_with_fusion(query: str, index, standards, keyword_weights=None, w_semantic=0.7, w_keyword=0.3):
    """
    Combine semantic score with a simple keyword overlap score.
    Tune w_semantic / w_keyword by testing on sample queries.
    """
    candidates = search(query, index, standards, top_k=10)
    query_terms = set(query.lower().split())

    for c in candidates:
        title_terms = set(c["title"].lower().split())
        overlap = len(query_terms & title_terms) / max(len(query_terms), 1)
        c["keyword_score"] = overlap
        c["final_score"] = w_semantic * c["semantic_score"] + w_keyword * overlap

    return sorted(candidates, key=lambda x: x["final_score"], reverse=True)


if __name__ == "__main__":
    # quick smoke test
    sample_standards = [
        {"code": "IS 456", "title": "Plain and Reinforced Concrete - Code of Practice", "scope": "concrete structures civil engineering"},
        {"code": "IS 800", "title": "General Construction in Steel - Code of Practice", "scope": "steel structures design fabrication"},
    ]
    idx, data = build_index(sample_standards)
    print(json.dumps(rank_with_fusion("steel structure design code", idx, data), indent=2))
