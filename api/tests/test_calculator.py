import pytest
from app.agents.calculator import calculate_market_projection


def test_apac_2030_calculation():
    success, derived, explanation = calculate_market_projection("What might the APAC market be worth in 2030?")
    assert success
    assert derived is not None
    assert "APAC Smart Water Meter Market" in derived["metric"]
    assert "Base-Year Ambiguity" in derived["assumptions"][1]
    assert "3.03" in derived["result"] or "7.08" in derived["result"]


def test_forecast_horizon_escalation_2035():
    success, derived, explanation = calculate_market_projection("What will the market be worth in 2035?")
    assert not success
    assert derived is None
    assert "Escalation Required" in explanation
    assert "2030" in explanation
