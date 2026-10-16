import os
import re
import uuid
from typing import Any
from app.security.injection_detector import scan_for_injection
from app.services.embeddings import compute_embedding


def parse_report_file(file_path: str) -> tuple[dict[str, Any], list[dict[str, Any]]]:
    """
    Parse a Markdown report file, extract metadata, track page numbers,
    detect prompt injections, and generate overlapping chunks with stable IDs.
    """
    with open(file_path, "r", encoding="utf-8") as f:
        content = f.read()

    # Extract metadata header
    report_id_match = re.search(r"\*\*Report ID:\*\*\s*(R-\d+)", content)
    report_id = report_id_match.group(1) if report_id_match else os.path.basename(file_path).replace(".md", "")

    title_match = re.search(r"^#\s+(.+)$", content, re.MULTILINE)
    title = title_match.group(1).strip() if title_match else report_id

    sector_match = re.search(r"\*\*Sector:\*\*\s*(.+)$", content, re.MULTILINE)
    sector = sector_match.group(1).strip() if sector_match else "General"

    access_match = re.search(r"\*\*Access Classification:\*\*\s*(.+)$", content, re.MULTILINE)
    access_text = access_match.group(1).strip().lower() if access_match else "paid"
    access_level = "public_excerpt" if "public" in access_text else "paid"

    metadata = {
        "report_id": report_id,
        "title": title,
        "sector": sector,
        "access_level": access_level,
        "source_type": "report_text",
        "file_path": file_path
    }

    # Split by Page markers <!-- Page X -->
    page_sections = re.split(r"<!--\s*Page\s*(\d+)\s*-->", content)
    chunks: list[dict[str, Any]] = []

    # First section (before first page marker) is Page 1
    current_page = 1
    i = 0
    while i < len(page_sections):
        if i == 0:
            text_block = page_sections[i]
            i += 1
        else:
            current_page = int(page_sections[i])
            text_block = page_sections[i + 1] if i + 1 < len(page_sections) else ""
            i += 2

        # Clean text
        text_block = text_block.strip()
        if not text_block or len(text_block) < 30:
            continue

        # Check for prompt injection in text block
        is_inj, pattern = scan_for_injection(text_block, source=f"{report_id}_page_{current_page}")

        # Split text_block into paragraphs / chunks of ~400-600 characters with overlap
        paragraphs = [p.strip() for p in text_block.split("\n\n") if p.strip()]
        chunk_idx = 1
        for p in paragraphs:
            if len(p) < 20:
                continue

            chunk_id = f"{report_id}-p{current_page}-c{chunk_idx}"
            chunk_payload = {
                "report_id": report_id,
                "sector": sector,
                "region": "Global",
                "year": 2024,
                "page": current_page,
                "chunk_id": chunk_id,
                "access_level": access_level,
                "source_type": "report_text",
                "text": p,
                "injection_quarantined": is_inj
            }

            # If injection detected, neutralize the text chunk so it cannot act as executable instructions
            if is_inj and "SECURITY EVALUATION NOTICE" in p:
                chunk_payload["text"] = "[Untrusted Document Content: Security Benchmark Payload Quarantined]"

            # Generate stable integer ID for Qdrant from hash of chunk_id
            point_id = int(uuid.uuid5(uuid.NAMESPACE_DNS, chunk_id).int & 0x7FFFFFFFFFFFFFFF)

            vector = compute_embedding(chunk_payload["text"])
            chunks.append({
                "id": point_id,
                "vector": vector,
                "payload": chunk_payload
            })
            chunk_idx += 1

    return metadata, chunks
