-- Migration 0014: LMS Analytics (SOC2-compliant)
-- All data is anonymised: IP is hashed (SHA-256), no raw PII stored

CREATE TABLE IF NOT EXISTS analytics_pageviews (
  id           BIGINT UNSIGNED   PRIMARY KEY AUTO_INCREMENT,
  session_hash CHAR(64)          NOT NULL          COMMENT 'SHA-256(IP+UserAgent+Date) — anonymous session ID',
  ip_hash      CHAR(64)          NOT NULL          COMMENT 'SHA-256(IP) — never raw IP',
  path         VARCHAR(500)      NOT NULL          COMMENT 'URL path only, no query params with PII',
  page_title   VARCHAR(255),
  referrer     VARCHAR(500),
  country_code CHAR(2),
  country_name VARCHAR(80),
  city         VARCHAR(80),
  device_type  ENUM('desktop','mobile','tablet','bot','unknown') DEFAULT 'unknown',
  browser      VARCHAR(60),
  os           VARCHAR(60),
  is_logged_in TINYINT(1)        DEFAULT 0,
  user_role    VARCHAR(30)       COMMENT 'admin/student/guest — no user_id stored',
  duration_sec SMALLINT UNSIGNED DEFAULT 0         COMMENT 'Time on page (updated via beacon)',
  created_at   TIMESTAMP         DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pv_date (created_at),
  INDEX idx_pv_path (path(100)),
  INDEX idx_pv_session (session_hash),
  INDEX idx_pv_country (country_code),
  INDEX idx_pv_device (device_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_events (
  id           BIGINT UNSIGNED   PRIMARY KEY AUTO_INCREMENT,
  session_hash CHAR(64)          NOT NULL,
  event_type   VARCHAR(60)       NOT NULL  COMMENT 'login,logout,enroll,complete,quiz_pass,quiz_fail,video_play,download',
  entity_type  VARCHAR(40)                 COMMENT 'course,lesson,quiz',
  entity_id    INT UNSIGNED,
  created_at   TIMESTAMP         DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ev_type (event_type),
  INDEX idx_ev_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SOC2: Add analytics opt-out to settings
INSERT IGNORE INTO settings (`key`, value, type, label, group_name) VALUES
('analytics_enabled',      '1',  'boolean',  'Enable Analytics',                'general'),
('analytics_retention_days','90', 'text',    'Data Retention (days)',           'general'),
('analytics_anonymize_ip',  '1',  'boolean', 'Anonymize IPs (SOC2 required)',   'general');
