import os
import sys
import time
import argparse
from app.ingestion.chunker import parse_report_file
from app.services.vector_store import init_vector_collection, upsert_chunks
from app.services.db import init_database_tables


def run_ingestion(reports_dir: str = "reports", force_reindex: bool = False) -> dict:
    start_time = time.time()
    
    # Initialize database tables and vector collection
    init_database_tables()
    init_vector_collection(recreate=force_reindex)

    if not os.path.isabs(reports_dir):
        # Resolve relative to project root
        base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
        reports_dir = os.path.join(base_dir, reports_dir)

    if not os.path.exists(reports_dir):
        raise FileNotFoundError(f"Reports directory not found: {reports_dir}")

    report_files = [
        os.path.join(reports_dir, f) 
        for f in os.listdir(reports_dir) 
        if f.endswith(".md")
    ]

    total_chunks = 0
    detected_injections = 0
    all_chunks = []

    for fpath in sorted(report_files):
        meta, chunks = parse_report_file(fpath)
        for c in chunks:
            if c["payload"].get("injection_quarantined"):
                detected_injections += 1
        all_chunks.extend(chunks)

    # Upsert in batches of 50
    batch_size = 50
    for i in range(0, len(all_chunks), batch_size):
        batch = all_chunks[i:i + batch_size]
        upsert_chunks(batch)

    total_chunks = len(all_chunks)
    duration_ms = int((time.time() - start_time) * 1000)

    return {
        "indexed_reports": len(report_files),
        "total_chunks": total_chunks,
        "detected_injections": detected_injections,
        "duration_ms": duration_ms
    }


def main():
    parser = argparse.ArgumentParser(description="Project Atlas Knowledge Base Ingestion CLI")
    parser.add_argument("--all", action="store_true", help="Ingest all reports in reports/ directory")
    parser.add_argument("--dir", default="reports", help="Directory containing report files")
    parser.add_argument("--force", action="store_true", help="Recreate Qdrant vector collection")

    args = parser.parse_args()
    print(f"[*] Starting Project Atlas ingestion pipeline from '{args.dir}'...")
    stats = run_ingestion(reports_dir=args.dir, force_reindex=args.force)
    print(f"[+] Ingestion complete in {stats['duration_ms']}ms:")
    print(f"    - Indexed Reports:      {stats['indexed_reports']}")
    print(f"    - Total Chunks:         {stats['total_chunks']}")
    print(f"    - Detected Injections:  {stats['detected_injections']} (Logged & Quarantined)")


if __name__ == "__main__":
    main()
