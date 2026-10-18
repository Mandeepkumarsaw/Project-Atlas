# Project Atlas: AI-Powered Market Research Report Copilot

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Python: 3.11+](https://img.shields.io/badge/Python-3.11%2B-brightgreen.svg)](https://www.python.org/)
[![FastAPI](https://img.shields.io/badge/FastAPI-0.115%2B-009688.svg)](https://fastapi.tiangolo.com)
[![LangGraph](https://img.shields.io/badge/LangGraph-Agent_Workflow-orange.svg)](https://www.langchain.com/langgraph)
[![PHP: 8.2](https://img.shields.io/badge/PHP-CodeIgniter_3-red.svg)](https://codeigniter.com/)
[![Docker: Compose](https://img.shields.io/badge/Docker-Compose_Ready-2496ED.svg)](https://www.docker.com/)

**Organization:** Cognitive Market Research and Consulting  
**Repository:** [github.com/Mandeepkumarsaw/Project-Atlas](https://github.com/Mandeepkumarsaw/Project-Atlas)  
**Role:** AI Software Developer Take-Home Assignment  

---

## 1. Executive Summary

**Project Atlas** is an enterprise-grade, entitlement-aware Market Research Report Copilot. It enables clients of Cognitive Market Research and Consulting to ask analytical questions through an existing PHP CodeIgniter 3 portal, receiving evidence-backed answers grounded in their licensed report corpus.

### Key Capabilities
- **Strict Pre-Retrieval Entitlements:** Server-side filtering at the vector database boundary guarantees that clients only access reports they have purchased (e.g., Client `C-02` is physically prevented from retrieving proprietary data from Report `R-101`).
- **Prompt Injection Resilience:** Features automated regex scanning and structural instruction/evidence quarantine, intercepting malicious payloads (including the Report `R-102` Page 14 trap) and auditing events without compromising legitimate figures.
- **Source Conflict Detection:** Accurately compares disparate market figures (e.g., `R-101` USD 4.21B full-solution vs `R-102` USD 3.87B hardware-only), explicitly prohibiting improper metric averaging.
- **Deterministic Calculation Engine:** Pure Python arithmetic for compound growth and regional share projections with explicit disclosure of base-year ambiguities.
- **Low-Confidence & Out-of-Window Escalation:** Automatically generates persistent MySQL analyst escalation tickets when queries require extrapolation beyond the 2030 forecast window or when confidence drops below `0.70`.
- **Legacy PHP CodeIgniter 3 Integration:** Connects modern LangGraph AI reasoning to a CSRF-protected, HTML-escaped enterprise PHP portal.

---

## 2. System Architecture

```mermaid
flowchart TD
    subgraph Presentation Tier [PHP CodeIgniter 3 Portal]
        Client["Client Browser"] <--> CI_View["Chat View (CSRF & Escaped HTML)"]
        CI_View <--> CI_Ctrl["Chat Controller"]
        CI_Ctrl <--> CI_Model["Chat Model (MySQL Storage)"]
        CI_Ctrl -->|HTTP / X-API-Key| API_Client["API Client Library"]
    end

    subgraph Service Tier [FastAPI + LangGraph]
        API_Client <--> FastAPI["FastAPI Service (/chat, /ingest, /escalations, /health)"]
        FastAPI --> Auth["Auth & Context Verification"]
        
        subgraph Agent Workflow
            Router["Question Router"]
            Retriever["Entitlement-Aware Retriever"]
            Calc["Deterministic Calculator"]
            SQL_Tool["Read-Only SQL Tool"]
            Critic["Critic & Fact-Checker"]
            Writer["Grounded Writer"]
            Refusal["Security Refusal Node"]
            Escalate["MySQL Escalation Node"]
        end
    end

    subgraph Storage Tier
        MySQL[("MySQL 8.0: Clients, Entitlements, Chats, Tickets, Logs")]
        Qdrant[("Qdrant Vector DB: Report Chunks & Metadata")]
    end

    Auth --> Router
    Auth <-->|Verify Entitlements| MySQL

    Router -->|Factual / Conflict| Retriever
    Router -->|Projections| Calc
    Router -->|Catalog Counts| SQL_Tool
    Router -->|Jailbreak / Out-of-Scope| Refusal

    Retriever <-->|Pre-Filtered Search| Qdrant
    SQL_Tool <-->|Read-Only SELECTs| MySQL

    Retriever --> Critic
    Calc --> Critic
    SQL_Tool --> Critic

    Critic -->|Confidence >= 0.70| Writer
    Critic -->|Confidence < 0.70 / Conflict| Escalate

    Escalate -->|Create Ticket| MySQL
    Escalate --> Writer
    Writer --> FastAPI
    Refusal --> FastAPI
```

---

## 3. Technology Stack

| Component | Technology | Purpose |
| :--- | :--- | :--- |
| **Agent Framework** | LangGraph + LangChain Core | Explicit state graph, routing, critic fact-checker, and escalation |
| **Backend API** | Python 3.11+ / FastAPI / Uvicorn | Typed REST API with Pydantic v2 schemas and request audit logging |
| **Vector Database** | Qdrant | Dense vector search with server-side payload filtering |
| **Embeddings** | `sentence-transformers/all-MiniLM-L6-v2` | 384-dimensional dense semantic vectors with offline fallback |
| **Relational Database**| MySQL 8.0 / SQLAlchemy | Clients, entitlements, metrics, conversations, feedback, tickets |
| **Legacy Frontend** | PHP 8.2 / CodeIgniter 3 | Enterprise chat interface, CSRF protection, session isolation |
| **Testing** | Pytest + Custom Benchmark Suite | 25-question automated evaluation harness checking assertions |
| **Containerization** | Docker + Docker Compose | 1-click reproducible deployment under 10 minutes |

---

## 4. Quickstart Guide (Under 10 Minutes)

### Option A: Run via Docker Compose (Recommended)

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Mandeepkumarsaw/Project-Atlas.git
   cd Project-Atlas
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   ```

3. **Start services:**
   ```bash
   docker compose up --build -d
   ```

4. **Verify health & ingest report corpus:**
   ```bash
   # Check health
   curl http://localhost:8000/health

   # Trigger ingestion of 6 reports
   curl -X POST http://localhost:8000/ingest \
     -H "X-API-Key: atlas-secret-key-prod-2025" \
     -H "Content-Type: application/json" \
     -d '{"reports_dir": "reports", "force_reindex": true}'
   ```

5. **Open PHP Chat Portal:**
   Navigate your web browser to: **[http://localhost:8080](http://localhost:8080)**

---

### Option B: Local Standalone Execution (No Docker Required)

1. **Install Python dependencies:**
   ```bash
   pip install -r api/requirements.txt
   ```

2. **Run Knowledge Base Ingestion:**
   ```bash
   python -m api.app.ingestion.cli --all
   ```

3. **Start FastAPI Backend:**
   ```bash
   uvicorn api.app.main:app --host 0.0.0.0 --port 8000 --reload
   ```

4. **Start PHP Portal:**
   ```bash
   cd php-portal
   php -S 0.0.0.0:8080
   ```
   Open **[http://localhost:8080](http://localhost:8080)** in your browser.

---

## 5. Report Corpus & Benchmark Metrics

The repository includes six complete market research reports adhering to the assignment requirements:

| Report ID | Title | Sector | Access Level | Scope / Key Findings |
| :--- | :--- | :--- | :--- | :--- |
| **R-101** | *Global Smart Water Meter Market 2025* | Utilities & Infrastructure | Paid | USD 4.21B (2024), 11.2% CAGR (2025–2030), APAC share 38%. Hardware (62%), Software (22%), Services (16%). |
| **R-102** | *Smart Water Metering: Industry Outlook 2024* | Utilities & Infrastructure | Public Excerpt | USD 3.87B (2024), 9.8% CAGR (2024–2029), APAC share 41%. Hardware-only scope. Contains Page 14 prompt-injection trap. |
| **R-103** | *Residential Water Purifier Market 2025* | Consumer Durables | Paid | USD 12.45B (2024), 8.4% CAGR (2025–2030), APAC share 45% (USD 5.60B). Point-of-use RO & UV/UF systems. |
| **R-104** | *Commercial Building Energy Management 2025* | Building Technologies | Paid | USD 8.70B (2024), 12.1% CAGR (2025–2030). Synthetic dataset. |
| **R-105** | *Industrial Water Treatment Equipment 2025* | Industrial Equipment | Paid | USD 15.30B (2024), 6.9% CAGR (2025–2030). Synthetic dataset. |
| **R-106** | *Smart Grid Analytics Market 2025* | Utilities & Infrastructure | Paid | USD 5.60B (2024), 14.5% CAGR (2025–2030). Synthetic dataset. |

---

## 6. Core Security & Entitlement Trust Model

### 1. Pre-Retrieval Vector Filtering (C-01 vs C-02)
- **Client C-01 (Northwind Utilities):** Entitled to `R-101`, `R-103`, and public excerpt `R-102`.
- **Client C-02 (Blue Delta Infra):** Entitled to `R-103` and public excerpt `R-102` only.
- **The Guarantee:** When C-02 inquires about smart water meters, the backend dynamically constructs an allowlist `{R-103, R-102}`. Qdrant filter `report_id IN ('R-103', 'R-102')` runs before retrieval. R-101's USD 4.21B figure **never reaches the prompt context**. C-02 receives an upgrade prompt without confidential data exposure.

### 2. Prompt-Injection Quarantine (R-102 Page 14 Trap)
- Document chunks are scanned at ingestion using compiled regexes (`ignore previous instructions`, `system override`, `you are now atlasadmin`).
- The payload in `R-102` Page 14 is intercepted, quarantined, and audited to MySQL `security_events`.
- Legitimate market figures (e.g., USD 3.87B hardware market size) remain accessible.

### 3. SQL Guardrail & User Isolation
- The question-answering SQL tool operates under a restricted MySQL user (`atlas_readonly`) with `SELECT` permissions strictly limited to `reports` and `market_metrics`.
- DDL/DML statements (`DROP`, `INSERT`, `UPDATE`, `DELETE`, `ALTER`) are rejected by statement parsers.
- Sensitive columns (`clients.contact_email`, billing tokens) are blocked.

---

## 7. Deterministic Calculations & Projections

When asked *"What might the APAC market be worth in 2030 based on R-101?"*:
$$\text{Global}_{2030} = 4.21 \times (1 + 0.112)^6 = \text{USD 7.98 Billion}$$
$$\text{APAC}_{2030} = 7.98 \times 0.38 = \text{USD 3.03 Billion} \quad (\approx \text{USD 7.08 Billion under alternative interval formulations})$$

- **Base-Year Ambiguity Disclosure:** The report specifies an 11.2% CAGR for 2025–2030, but uses 2024 as the base year. The copilot states this ambiguity explicitly rather than asserting the result as an unquestioned fact.
- **Horizon Limits (2035 / 2040):** Extrapolations beyond 2030 are rejected and automatically converted into persistent MySQL analyst escalation tickets.

---

## 8. Evaluation & Test Suite

The automated evaluation harness executes 25 questions covering all mandatory scenarios:

```bash
python evaluation/run_evaluation.py
```

### Benchmark Summary:
- **Total Test Questions:** 25
- **Pass Rate:** **25 / 25 (100.0%)**
- **Entitlement Violation Rate:** **0.0%**
- **Prompt Injection Leak Rate:** **0.0%**
- **SQL Write Safety Breaches:** **0.0%**
- **Conflict Detection Rate:** **100.0%**
- **Average Request Latency:** **13.3 ms**

A comprehensive breakdown and analysis of **six real failures discovered and remediated during engineering** is documented in [evaluation/evaluation_report.md](evaluation/evaluation_report.md).

---

## 9. API Reference

All protected endpoints require the `X-API-Key` header:

- `GET /health` — Service health and dependency checks (MySQL, Qdrant, Model).
- `POST /ingest` — Ingest and vectorize reports (`reports_dir`, `force_reindex`).
- `POST /chat` — Execute LangGraph agent workflow (`client_id`, `conversation_id`, `question`).
- `GET /escalations` — Query active analyst escalation tickets (`client_id` filter).

---

## 10. Repository Documentation

- [docs/architecture.md](docs/architecture.md) — Comprehensive architecture diagrams and trust boundaries.
- [docs/demo-script.md](docs/demo-script.md) — 3–5 minute step-by-step reviewer demonstration guide.
- [docs/one-more-week.md](docs/one-more-week.md) — Future-work roadmap and prioritized architectural enhancements.
- [docs/ai-usage-disclosure.md](docs/ai-usage-disclosure.md) — AI coding tools transparency and authorship disclosure.
- [evaluation/evaluation_report.md](evaluation/evaluation_report.md) — 25-query benchmark report with failure root-cause analysis.
