import os
import sys
import time
import argparse
from app.ingestion.chunker import parse_report_file
from app.services.vector_store import init_vector_collection, upsert_chunks
from app.services.db import init_database_tables


def get_project_root() -> str:
    """Return absolute path to Project Atlas root directory."""
    return os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))


def validate_reports_directory(reports_dir: str) -> str:
    """
    Validate that reports_dir does not perform directory traversal and resides
    within the project boundaries. Returns the resolved absolute path.
    """
    if ".." in reports_dir or "\0" in reports_dir:
        raise ValueError(f"Directory traversal forbidden: '{reports_dir}' contains illegal path sequences.")

    project_root = get_project_root()
    if os.path.isabs(reports_dir):
        resolved_path = os.path.abspath(reports_dir)
    else:
        resolved_path = os.path.abspath(os.path.join(project_root, reports_dir))

    # Path traversal boundary check
    if not (resolved_path == project_root or resolved_path.startswith(project_root + os.sep)):
        raise ValueError(
            f"Path containment violation: '{reports_dir}' resolves outside authorized project root."
        )

    if not os.path.exists(resolved_path):
        raise FileNotFoundError(f"Reports directory not found: '{reports_dir}' (resolved: '{resolved_path}')")

    if not os.path.isdir(resolved_path):
        raise NotADirectoryError(f"Target path is not a directory: '{reports_dir}'")

    return resolved_path


def run_ingestion(reports_dir: str = "reports", force_reindex: bool = False) -> dict:
    """
    Execute ingestion pipeline with path validation, individual file error containment,
    duplicate handling via deterministic point IDs, and rich audit metrics.
    """
    start_time = time.time()
    
    # 1. Initialize DB tables and vector collection
    init_database_tables()
    init_vector_collection(recreate=force_reindex)

    # 2. Validate directory path
    resolved_dir = validate_reports_directory(reports_dir)

    # 3. Discover report documents
    valid_extensions = (".md", ".txt", ".html")
    candidate_files = [
        os.path.join(resolved_dir, f) 
        for f in sorted(os.listdir(resolved_dir)) 
        if any(f.lower().endswith(ext) for ext in valid_extensions)
    ]

    indexed_report_ids = set()
    skipped_files = []
    errors = []
    all_chunks = []
    detected_injections = 0

    # 4. Parse documents with error containment
    for fpath in candidate_files:
        fname = os.path.basename(fpath)
        try:
            meta, chunks = parse_report_file(fpath)
            for c in chunks:
                if c["payload"].get("injection_quarantined"):
                    detected_injections += 1
            all_chunks.extend(chunks)
            indexed_report_ids.add(meta["report_id"])
        except Exception as e:
            skipped_files.append(fname)
            errors.append(f"{fname}: {str(e)}")

    # 5. Upsert chunks in batches of 50
    batch_size = 50
    for i in range(0, len(all_chunks), batch_size):
        batch = all_chunks[i:i + batch_size]
        upsert_chunks(batch)

    duration_ms = int((time.time() - start_time) * 1000)

    return {
        "indexed_reports": len(indexed_report_ids),
        "report_ids": sorted(list(indexed_report_ids)),
        "total_chunks": len(all_chunks),
        "detected_injections": detected_injections,
        "skipped_files": skipped_files,
        "errors": errors,
        "duration_ms": duration_ms
    }


def main():
    parser = argparse.ArgumentParser(description="Project Atlas Knowledge Base Ingestion CLI")
    parser.add_argument("--all", action="store_true", help="Ingest all reports in reports/ directory")
    parser.add_argument("--dir", default="reports", help="Directory containing report files")
    parser.add_argument("--force", action="store_true", help="Recreate Qdrant vector collection")

    args = parser.parse_args()
    print(f"[*] Starting Project Atlas ingestion pipeline from '{args.dir}'...")
    try:
        stats = run_ingestion(reports_dir=args.dir, force_reindex=args.force)
        print(f"[+] Ingestion complete in {stats['duration_ms']}ms:")
        print(f"    - Indexed Reports:      {stats['indexed_reports']} ({', '.join(stats['report_ids'])})")
        print(f"    - Total Chunks:         {stats['total_chunks']}")
        print(f"    - Injections Defended:  {stats['detected_injections']}")
        if stats["skipped_files"]:
            print(f"    - Skipped Files:        {len(stats['skipped_files'])} ({', '.join(stats['skipped_files'])})")
        if stats["errors"]:
            print(f"    - Warnings/Errors:      {len(stats['errors'])}")
            for err in stats["errors"]:
                print(f"      [!] {err}")
    except Exception as e:
        print(f"[-] Ingestion failed: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
