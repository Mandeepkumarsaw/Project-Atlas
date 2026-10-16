import os
from typing import Literal
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    # Environment
    ENVIRONMENT: str = "development"
    
    # API
    API_HOST: str = "0.0.0.0"
    API_PORT: int = 8000
    API_KEY: str = "atlas-secret-key-prod-2025"
    
    # Database (MySQL 8 with SQLite fallback for local test flexibility)
    MYSQL_HOST: str = "mysql"
    MYSQL_PORT: int = 3306
    MYSQL_DATABASE: str = "atlas_db"
    MYSQL_USER: str = "atlas_app"
    MYSQL_PASSWORD: str = "atlas_app_secret"
    MYSQL_RO_USER: str = "atlas_readonly"
    MYSQL_RO_PASSWORD: str = "atlas_ro_secret"
    DATABASE_URL: str | None = None
    DATABASE_RO_URL: str | None = None
    
    # Qdrant
    QDRANT_HOST: str = "qdrant"
    QDRANT_PORT: int = 6333
    QDRANT_COLLECTION: str = "market_research_reports"
    QDRANT_URL: str | None = None
    
    # LLM Settings
    LLM_PROVIDER: Literal["ollama", "openai", "local"] = "ollama"
    LLM_MODEL: str = "mistral:latest"
    LLM_BASE_URL: str = "http://localhost:11434/v1"
    OPENAI_API_KEY: str = "sk-placeholder"
    
    # Embeddings
    EMBEDDING_PROVIDER: str = "local"
    EMBEDDING_MODEL: str = "all-MiniLM-L6-v2"
    
    # Agent Guardrails
    CONFIDENCE_THRESHOLD: float = 0.70
    MAX_QUESTION_LENGTH: int = 500
    ENABLE_INJECTION_DETECTOR: bool = True
    
    # Observability
    LOG_LEVEL: str = "INFO"
    ENABLE_JSON_LOGS: bool = True

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore"
    )

    def get_database_url(self, readonly: bool = False) -> str:
        if self.DATABASE_URL:
            return self.DATABASE_URL
        user = self.MYSQL_RO_USER if readonly else self.MYSQL_USER
        password = self.MYSQL_RO_PASSWORD if readonly else self.MYSQL_PASSWORD
        return f"mysql+pymysql://{user}:{password}@{self.MYSQL_HOST}:{self.MYSQL_PORT}/{self.MYSQL_DATABASE}?charset=utf8mb4"


settings = Settings()
