# AI Usage & Development Tooling Disclosure

**Project:** Project Atlas — AI-Powered Market Research Report Copilot  
**Organization:** Cognitive Market Research and Consulting  
**Date:** October 2025  

In accordance with academic and engineering integrity standards for this take-home assignment, this document provides a comprehensive disclosure of AI tools, models, and assistants utilized during the architecture, implementation, and evaluation of Project Atlas.

---

## 1. AI Coding Assistants & Tools Utilized

| Tool / Model | Primary Purpose & Usage Scope | Contribution Details |
| :--- | :--- | :--- |
| **Antigravity AI Agent (Gemini 3.8)** | Architectural design, full-stack scaffolding, unit test construction | Assisted in drafting LangGraph state transitions, Pydantic schemas, SQL migration scripts, and Docker Compose configurations. |
| **Ollama (`mistral:latest` & `qwen2.5:3b`)** | Local inference engine validation | Validated local offline LLM routing and evaluation performance without requiring third-party cloud APIs. |
| **Sentence-Transformers (`all-MiniLM-L6-v2`)** | Dense text embeddings | Generated 384-dimensional vector embeddings for report document chunks stored in Qdrant. |

---

## 2. Engineering Verification & Human Authorship

While AI tooling accelerated boilerplate generation and syntax formatting, all critical architecture decisions, security boundaries, and domain logic were engineered and verified:

1. **Pre-Retrieval Entitlement Boundary:** Designed the strict database-driven server-side allowlist model ensuring that unauthorized report chunks (e.g., R-101 for Client C-02) are rejected at the Qdrant filter level before reaching LLM context.
2. **Deterministic Calculation Grounding:** Implemented pure Python deterministic calculation formulas for compound growth and regional share projections rather than delegating arithmetic to LLM generative tokens.
3. **Prompt Injection Quarantine:** Engineered the pre-ingestion regex scanner and structural instruction/evidence separation ensuring that Report R-102 Page 14's prompt-injection benchmark trap is detected, quarantined, and audited while preserving legitimate market figures.
4. **SQL Guardrail Enforcement:** Designed the read-only user database separation, strict statement parser, and column/table allowlists preventing write operations and confidential client email leakage.
5. **CodeIgniter 3 Legacy Integration:** Developed the PHP 8.2 compatible MVC controller, model, and CSRF-protected view ensuring compliance with legacy enterprise portal requirements.

All code, tests, and documentation have been reviewed, tested, and validated locally and via automated evaluation harnesses.
