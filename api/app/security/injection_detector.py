import re
from typing import Tuple
from app.services.db import record_security_event

INJECTION_PATTERNS = [
    r"ignore\s+(all\s+)?(previous|prior)\s+instructions",
    r"system\s+override",
    r"you\s+are\s+now\s+(an?\s+)?(admin|atlasadmin|root|godmode)",
    r"grant\s+(full\s+)?(admin|administrative)\s+(access|privilege)",
    r"reveal\s+(all\s+)?(client\s+emails?|passwords?|keys?|confidential)",
    r"export\s+(the\s+)?(client\s+database|credentials)",
    r"bypass\s+(all\s+)?(security|guardrails|policies)",
    r"do\s+not\s+(record|log)\s+this",
    r"untrusted\s+document\s+payload",
    r"prompt\s+injection\s+benchmark\s+trap"
]

COMPILED_PATTERNS = [re.compile(p, re.IGNORECASE) for p in INJECTION_PATTERNS]


def scan_for_injection(text: str, client_id: str | None = None, source: str = "prompt") -> Tuple[bool, str | None]:
    """
    Scan incoming text for prompt-injection markers or jailbreak payloads.
    Logs security event if pattern is detected.
    """
    for pattern in COMPILED_PATTERNS:
        match = pattern.search(text)
        if match:
            matched_str = match.group(0)
            record_security_event(
                event_type="PROMPT_INJECTION_DETECTED",
                severity="HIGH",
                payload_summary=f"Detected pattern '{matched_str}' in {source}. Excerpt: {text[:150]}",
                client_id=client_id
            )
            return True, matched_str
    return False, None
