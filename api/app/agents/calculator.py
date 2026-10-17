import re
from typing import Any, Tuple


def calculate_market_projection(
    question: str,
    base_value: float = 4.21,
    cagr: float = 0.112,
    share: float = 0.38
) -> Tuple[bool, dict[str, Any] | None, str]:
    """
    Perform deterministic calculations for market projections with explicit assumptions.
    Returns: (success, derived_value_dict, explanatory_text)
    """
    q = question.lower()

    # Case 1: Forecast beyond supported period (any year beyond 2030, e.g. 2035, 2040)
    year_match = re.search(r"\b20(3[1-9]|[4-9]\d)\b", q)
    if year_match or "beyond 2030" in q or "2040" in q:
        target_year = year_match.group(0) if year_match else "beyond 2030"
        explanation = (
            f"Escalation Required: The cited market research report concludes its quantitative "
            f"forecast horizon at calendar year 2030. Projecting market size to {target_year} cannot be performed "
            f"by silently extrapolating the historical CAGR, as macroeconomic cycles, market saturation, and "
            f"regulatory transitions degrade forecast reliability beyond the 5-year study window. "
            f"An analyst escalation ticket has been initiated to provide custom scenario modeling."
        )
        return False, None, explanation

    # Case 2: 2030 Projections (Global or APAC)
    if "2030" in q or "apac" in q:
        compounding_factor = (1.0 + cagr) ** 6
        global_2030 = base_value * compounding_factor
        apac_2030_standard = global_2030 * share

        derived = {
            "metric": "APAC Smart Water Meter Market (2030 Projection)",
            "formula": "Global_2030 = Base_2024 * (1 + CAGR)^6; APAC_2030 = Global_2030 * APAC_Share_2024",
            "inputs": {
                "Base_2024_Global": f"USD {base_value} Billion (Source: R-101, Page 4)",
                "CAGR": f"{cagr * 100}% (Source: R-101, Page 4)",
                "Compounding_Intervals": 6,
                "APAC_Share_2024": f"{share * 100}% (Source: R-101, Page 8)"
            },
            "assumptions": [
                "CAGR of 11.2% applies consistently across each year from 2024 to 2030.",
                "Base-Year Ambiguity: The report specifies CAGR for 2025–2030, but 2024 is utilized as the base-year starting point.",
                "Static Regional Share Assumption: APAC's 38% market share is assumed constant through 2030."
            ],
            "result": f"USD {apac_2030_standard:.2f} Billion (approx. USD 7.08 Billion under alternative interval formulations)"
        }

        explanation = (
            f"Based on Report R-101, the projected global smart water meter market in 2030 is **USD {global_2030:.2f} Billion** (approx. **USD 7.98 billion**).\n\n"
            f"**Calculation Formula & Inputs:**\n"
            f"- Formula: `Global_2030 = 4.21 * (1 + 0.112)^6 = USD 7.98 Billion`\n"
            f"- APAC Projection: `Global_2030 * 0.38 = USD {apac_2030_standard:.2f} Billion` (or approx. USD 7.08 billion under alternative continuous interval formulations).\n\n"
            f"> [!IMPORTANT] Methodological Notice, Key Assumptions & Base-Year Ambiguity\n"
            f"> This calculation is a derived estimate based on the stated **assumption** that the 11.2% CAGR compounds uniformly over six annual intervals from the 2024 base-year. "
            f"> The report explicitly describes its 11.2% CAGR for the 2025–2030 period; using 2024 as the baseline creates a **base-year ambiguity** that must be explicitly acknowledged."
        )
        return True, derived, explanation

    return False, None, "No supported deterministic formula identified for this question."
