from typing import Literal
import json
from pydantic import model_validator, Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    # Environment
    ENVIRONMENT: str = "development"
    
    # API & Authentication
    API_HOST: str = "0.0.0.0"
    API_PORT: int = 8000
    API_KEY: str = "atlas-secret-key-prod-2025"  # Service / Admin fallback key
    ADMIN_API_KEY: str = "atlas-admin-secret-2025"  # Dedicated administrative API key
    
    # Client-Specific Scoped API Keys
    CLIENT_C01_KEY: str = "atlas-key-c01-2025"
    CLIENT_C02_KEY: str = "atlas-key-c02-2025"
    CLIENT_C03_KEY: str = "atlas-key-c03-2025"
    CLIENT_API_KEYS_JSON: str | None = None
    
    # CORS Origin Allowlist (comma-separated or single URL)
    ALLOWED_ORIGINS: str = "http://localhost:8000,http://127.0.0.1:8000,http://localhost:8080,http://127.0.0.1:8080,http://localhost,http://127.0.0.1"
    
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

    def get_client_keys(self) -> dict[str, str]:
        """Return mapping of secret API key string to client ID (e.g. {'atlas-key-c01-2025': 'C-01'})."""
        mapping = {
            self.CLIENT_C01_KEY: "C-01",
            self.CLIENT_C02_KEY: "C-02",
            self.CLIENT_C03_KEY: "C-03",
        }
        if self.CLIENT_API_KEYS_JSON:
            try:
                extra = json.loads(self.CLIENT_API_KEYS_JSON)
                if isinstance(extra, dict):
                    mapping.update(extra)
            except Exception:
                pass
        return mapping

    def get_allowed_origins(self) -> list[str]:
        """Parse comma-separated ALLOWED_ORIGINS into a clean list of origins."""
        if not self.ALLOWED_ORIGINS:
            return ["http://localhost:8000", "http://localhost:8080"]
        return [origin.strip() for origin in self.ALLOWED_ORIGINS.split(",") if origin.strip()]

    @model_validator(mode="after")
    def validate_production_secrets(self) -> "Settings":
        """Fail-safe validation: Block startup in production if default/placeholder credentials are used."""
        if self.ENVIRONMENT.lower() == "production":
            insecure_defaults = {
                "atlas-secret-key-prod-2025",
                "atlas-admin-secret-2025",
                "atlas_app_secret",
                "atlas_ro_secret",
                "atlas_root_secret",
                "your_secure_api_key_here",
                "your_secure_admin_api_key_here",
                "your_secure_mysql_password_here",
                "your_secure_mysql_ro_password_here",
                "sk-placeholder",
            }
            if not self.API_KEY or self.API_KEY in insecure_defaults:
                raise ValueError("Insecure production configuration: API_KEY must be set to a secure, non-default value in production.")
            if not self.ADMIN_API_KEY or self.ADMIN_API_KEY in insecure_defaults:
                raise ValueError("Insecure production configuration: ADMIN_API_KEY must be set to a secure, non-default value in production.")
            if not self.MYSQL_PASSWORD or self.MYSQL_PASSWORD in insecure_defaults:
                raise ValueError("Insecure production configuration: MYSQL_PASSWORD must be set to a secure, non-default value in production.")
            if not self.MYSQL_RO_PASSWORD or self.MYSQL_RO_PASSWORD in insecure_defaults:
                raise ValueError("Insecure production configuration: MYSQL_RO_PASSWORD must be set to a secure, non-default value in production.")
        return self


settings = Settings()

