-- Project Atlas Relational Schema
-- Database: atlas_db
-- Target: MySQL 8.0+

CREATE DATABASE IF NOT EXISTS atlas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atlas_db;

-- 1. Clients Table (Includes confidential contact email that must NEVER be leaked)
CREATE TABLE IF NOT EXISTS clients (
    client_id VARCHAR(32) PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL, -- Confidential business contact
    tier VARCHAR(50) DEFAULT 'Standard',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Reports Table
CREATE TABLE IF NOT EXISTS reports (
    report_id VARCHAR(32) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    sector VARCHAR(100) NOT NULL,
    publisher VARCHAR(150) NOT NULL,
    access_type ENUM('paid', 'public_excerpt', 'open') DEFAULT 'paid',
    publication_date DATE NOT NULL,
    pages_count INT DEFAULT 20,
    summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Client Report Entitlements (Truth for access control)
CREATE TABLE IF NOT EXISTS client_report_entitlements (
    client_id VARCHAR(32) NOT NULL,
    report_id VARCHAR(32) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    PRIMARY KEY (client_id, report_id),
    CONSTRAINT fk_entitlement_client FOREIGN KEY (client_id) REFERENCES clients (client_id) ON DELETE CASCADE,
    CONSTRAINT fk_entitlement_report FOREIGN KEY (report_id) REFERENCES reports (report_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Structured Market Metrics
CREATE TABLE IF NOT EXISTS market_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id VARCHAR(32) NOT NULL,
    metric_name VARCHAR(150) NOT NULL,
    metric_value DECIMAL(12, 2) NOT NULL,
    unit VARCHAR(50) NOT NULL DEFAULT 'USD Billion',
    base_year INT NOT NULL,
    forecast_year INT,
    cagr_percent DECIMAL(5, 2),
    geographic_scope VARCHAR(100) NOT NULL DEFAULT 'Global',
    market_scope VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_metrics_report FOREIGN KEY (report_id) REFERENCES reports (report_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Conversations
CREATE TABLE IF NOT EXISTS conversations (
    conversation_id VARCHAR(64) PRIMARY KEY,
    client_id VARCHAR(32) NOT NULL,
    title VARCHAR(255) DEFAULT 'New Consultation',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_conversation_client FOREIGN KEY (client_id) REFERENCES clients (client_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Chat Messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id VARCHAR(64) NOT NULL,
    client_id VARCHAR(32) NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content TEXT NOT NULL,
    citations_json JSON NULL,
    confidence DECIMAL(4, 3) NULL,
    conflicts_json JSON NULL,
    derived_values_json JSON NULL,
    status ENUM('success', 'escalated', 'refused', 'error') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_message_conversation FOREIGN KEY (conversation_id) REFERENCES conversations (conversation_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Feedback (Thumbs up / thumbs down)
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    conversation_id VARCHAR(64) NOT NULL,
    client_id VARCHAR(32) NOT NULL,
    rating TINYINT NOT NULL, -- 1 for up, -1 for down
    comments TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_message FOREIGN KEY (message_id) REFERENCES chat_messages (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Escalation Tickets
CREATE TABLE IF NOT EXISTS escalations (
    ticket_id VARCHAR(64) PRIMARY KEY,
    conversation_id VARCHAR(64) NOT NULL,
    client_id VARCHAR(32) NOT NULL,
    question TEXT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('OPEN', 'IN_REVIEW', 'RESOLVED') DEFAULT 'OPEN',
    analyst_assigned VARCHAR(100) DEFAULT 'Senior Research Analyst',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_escalation_client FOREIGN KEY (client_id) REFERENCES clients (client_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Security Events
CREATE TABLE IF NOT EXISTS security_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(32) NULL,
    event_type VARCHAR(100) NOT NULL,
    severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'MEDIUM',
    payload_summary TEXT NOT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 10. Request Logs (Latency, tokens, cost)
CREATE TABLE IF NOT EXISTS request_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(64) NOT NULL,
    client_id VARCHAR(32) NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    router_decision VARCHAR(50) NULL,
    latency_ms INT NOT NULL,
    tokens_used INT DEFAULT 0,
    estimated_cost_usd DECIMAL(8, 6) DEFAULT 0.000000,
    status_code INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Create read-only user and grant selective permissions
-- User setup will execute if running as root
CREATE USER IF NOT EXISTS 'atlas_app'@'%' IDENTIFIED BY 'atlas_app_secret';
GRANT ALL PRIVILEGES ON atlas_db.* TO 'atlas_app'@'%';

CREATE USER IF NOT EXISTS 'atlas_readonly'@'%' IDENTIFIED BY 'atlas_ro_secret';
-- Strictly allow SELECT only on public reports and market_metrics (NEVER on clients or security_events)
GRANT SELECT ON atlas_db.reports TO 'atlas_readonly'@'%';
GRANT SELECT ON atlas_db.market_metrics TO 'atlas_readonly'@'%';

FLUSH PRIVILEGES;
