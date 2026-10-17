from pydantic import BaseModel


class HealthResponse(BaseModel):
    status: str
    mysql: str
    vector_db: str
    model: str
    environment: str
    version: str = "1.0.0"
