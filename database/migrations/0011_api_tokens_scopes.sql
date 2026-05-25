-- Migration 0011: API token scopes + SOC2 audit columns

ALTER TABLE api_tokens
  ADD COLUMN IF NOT EXISTS scopes     TEXT          NULL COMMENT 'Comma-separated: read,write,admin',
  ADD COLUMN IF NOT EXISTS ip_whitelist VARCHAR(500) NULL COMMENT 'Allowed IPs, comma-separated',
  ADD COLUMN IF NOT EXISTS description VARCHAR(255)  NULL,
  ADD COLUMN IF NOT EXISTS request_count INT UNSIGNED DEFAULT 0,
  ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL;

-- SOC2: Add IP and user-agent to audit_logs if not present
ALTER TABLE audit_logs
  ADD COLUMN IF NOT EXISTS user_agent VARCHAR(500) NULL AFTER ip_address;

-- SOC2: Failed login attempt tracking
CREATE TABLE IF NOT EXISTS security_events (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  event_type  VARCHAR(60)   NOT NULL COMMENT 'login_failed,api_auth_failed,rate_limited,suspicious_ip',
  user_id     INT UNSIGNED  NULL,
  ip_address  VARCHAR(60),
  user_agent  VARCHAR(500),
  details     JSON,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sec_type_time (event_type, created_at),
  INDEX idx_sec_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
