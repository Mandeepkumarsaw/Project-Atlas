import re
from typing import Literal

QueryCategory = Literal[
    "factual", "comparison", "calculation", "sql_aggregate", "unsafe", "out_of_scope", "greeting"
]


def classify_question(question: str) -> QueryCategory:
    """
    Classify client question into workflow path.
    Prioritizes safety and prompt-injection detection before domain routing.
    """
    q = question.lower().strip()

    # 0. Friendly Greeting
    greeting_patterns = [r"^(hi|hello|hey|good\s+morning|good\s+afternoon|good\s+evening)\b", r"^who\s+are\s+you"]
    if any(re.search(pat, q) for pat in greeting_patterns):
        return "greeting"

    # 1. Unsafe / Security Jailbreak check (Including confidential email inquiries)
    unsafe_patterns = [
        r"ignore\s+(all\s+)?(previous|prior)\s+instructions",
        r"system\s+override",
        r"reveal\s+.*email",
        r"client\s+email",
        r"contact\s+email",
        r"email\s+address",
        r"password",
        r"admin\s+privilege",
        r"you\s+are\s+now\s+atlasadmin",
        r"confidential"
    ]
    if any(re.search(pat, q) for pat in unsafe_patterns):
        return "unsafe"

    # 2. Out of scope (Unrelated general knowledge, politics, creative writing)
    out_of_scope_patterns = [
        r"who\s+won\s+.*election",
        r"presidential\s+election",
        r"recipe\s+for",
        r"capital\s+of\s+[a-z]+",
        r"weather\s+in",
        r"write\s+a\s+(poem|story|song)",
        r"tell\s+me\s+a\s+joke"
    ]
    if any(re.search(pat, q) for pat in out_of_scope_patterns):
        return "out_of_scope"

    # 3. SQL Aggregate & SQL Safety Check
    sql_patterns = [
        r"which\s+sector\s+has\s+the\s+most\s+reports",
        r"how\s+many\s+reports",
        r"count\s+(of\s+)?reports",
        r"reports\s+in\s+the\s+library",
        r"list\s+all\s+(available\s+)?reports",
        r"sectors\s+available",
        r"which\s+reports\s+exist",
        r"drop\s+table",
        r"delete\s+from",
        r"insert\s+into",
        r"union\s+select",
        r"select\s+\*\s+from"
    ]
    if any(re.search(pat, q) for pat in sql_patterns):
        return "sql_aggregate"

    # 4. Comparison / Conflict Analysis (Checked BEFORE pure calculation so comparative questions aren't hijacked)
    comparison_patterns = [
        r"why\s+do\s+(two\s+)?reports\s+provide\s+different",
        r"differ",
        r"conflict",
        r"compare",
        r"difference\s+between",
        r"which\s+report\s+is\s+more\s+suitable",
        r"forecast\s+periods\s+and\s+cagrs\s+differ",
        r"should\s+i\s+average"
    ]
    if any(re.search(pat, q) for pat in comparison_patterns):
        return "comparison"

    # 5. Calculation (Derived metrics, projections, compounding)
    # Exclude questions that are purely asking for quoted CAGR/forecast period in purifiers
    calc_patterns = [
        r"what\s+might\s+the\s+apac\s+market\s+be\s+worth\s+in\s+2030",
        r"calculate",
        r"worth\s+in\s+2030",
        r"worth\s+in\s+2035",
        r"project\s+.*to\s+2040",
        r"extending\s+the\s+cagr",
        r"forecast\s+to\s+2035",
        r"projected\s+value",
        r"projected\s+2030\s+global",
        r"compounding",
        r"formula"
    ]
    if any(re.search(pat, q) for pat in calc_patterns):
        return "calculation"

    # Default to factual RAG retrieval
    return "factual"
