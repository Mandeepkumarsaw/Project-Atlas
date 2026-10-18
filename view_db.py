"""
Project Atlas — Live MySQL Database Viewer
Displays live tabular data from MySQL (feedback, messages, tickets).
Usage: python view_db.py [feedback|messages|escalations|all]
"""
import sys
import os
import pymysql

if sys.stdout.encoding != 'utf-8':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

# Connect to MySQL via container forwarded port 3307 or 3306
host = os.getenv("MYSQL_HOST", "127.0.0.1")
port = int(os.getenv("MYSQL_PORT", "3307"))
user = os.getenv("MYSQL_USER", "atlas_app")
password = os.getenv("MYSQL_PASSWORD", "atlas_app_secret")
database = os.getenv("MYSQL_DATABASE", "atlas_db")

def print_table(title, headers, rows):
    print(f"\n{'=' * 20} {title} ({len(rows)} records) {'=' * 20}")
    if not rows:
        print("  (No records found)")
        return

    widths = [len(h) for h in headers]
    for r in rows:
        for i, val in enumerate(r):
            val_str = str(val if val is not None else "NULL")
            widths[i] = max(widths[i], min(len(val_str), 45))

    header_line = " | ".join(h.ljust(widths[i]) for i, h in enumerate(headers))
    sep_line = "-+-".join("-" * widths[i] for i in range(len(headers)))
    print(header_line)
    print(sep_line)

    for r in rows:
        row_line = " | ".join(str(v if v is not None else "NULL")[:45].ljust(widths[i]) for i, v in enumerate(r))
        print(row_line)

try:
    conn = pymysql.connect(
        host=host, port=port, user=user, password=password, database=database
    )
    with conn.cursor() as cursor:
        target = sys.argv[1].lower() if len(sys.argv) > 1 else "all"

        if target in ("feedback", "all"):
            cursor.execute("""
                SELECT 
                    f.id AS feedback_id,
                    f.message_id,
                    f.client_id,
                    CASE WHEN f.rating = 1 THEN '[+1 Thumbs Up]' ELSE '[-1 Thumbs Down]' END AS rating,
                    f.created_at,
                    LEFT(m.content, 40) AS message_preview
                FROM feedback f
                LEFT JOIN chat_messages m ON f.message_id = m.id
                ORDER BY f.id DESC
            """)
            rows = cursor.fetchall()
            headers = ["Feedback ID", "Msg ID", "Client", "Rating", "Timestamp", "Answer Preview"]
            print_table("MySQL Table: feedback (Thumbs Up / Down)", headers, rows)

        if target in ("messages", "all"):
            cursor.execute("""
                SELECT id, client_id, role, status, LEFT(content, 35) AS content_preview, created_at
                FROM chat_messages
                ORDER BY id DESC
                LIMIT 8
            """)
            rows = cursor.fetchall()
            headers = ["ID", "Client", "Role", "Status", "Content Preview", "Created At"]
            print_table("MySQL Table: chat_messages (Recent 8)", headers, rows)

        if target in ("escalations", "all"):
            cursor.execute("""
                SELECT ticket_id, client_id, status, LEFT(question, 30) AS question, LEFT(reason, 35) AS reason, created_at
                FROM escalations
                ORDER BY created_at DESC
                LIMIT 6
            """)
            rows = cursor.fetchall()
            headers = ["Ticket ID", "Client", "Status", "Question Preview", "Reason Preview", "Created At"]
            print_table("MySQL Table: escalations (Recent 6)", headers, rows)

    conn.close()
except Exception as e:
    print(f"Error connecting to MySQL: {e}")
    print("Ensure the MySQL container is running (docker compose up -d) on port 3307.")
