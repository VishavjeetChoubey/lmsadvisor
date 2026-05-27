-- Phase 29: AI Tutor & Personalization

CREATE TABLE IF NOT EXISTS ai_chat_sessions (
  id           INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  user_id      INT UNSIGNED  NOT NULL,
  course_id    INT UNSIGNED  NOT NULL,
  lesson_id    INT UNSIGNED  NULL,
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_chat_messages (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  session_id  INT UNSIGNED  NOT NULL,
  role        ENUM('user','assistant') NOT NULL,
  content     TEXT          NOT NULL,
  tokens_used SMALLINT UNSIGNED DEFAULT 0,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_acm_session (session_id),
  FOREIGN KEY (session_id) REFERENCES ai_chat_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Phase 30: Integrations Hub
CREATE TABLE IF NOT EXISTS webhooks (
  id           INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  uuid         CHAR(36)      NOT NULL UNIQUE,
  name         VARCHAR(120)  NOT NULL,
  url          VARCHAR(500)  NOT NULL,
  secret       VARCHAR(64)   NOT NULL COMMENT 'HMAC-SHA256 signing secret',
  events       VARCHAR(500)  NOT NULL COMMENT 'JSON array: enroll,complete,quiz,grade',
  is_active    TINYINT(1)    DEFAULT 1,
  last_fired   TIMESTAMP     NULL,
  fail_count   TINYINT UNSIGNED DEFAULT 0,
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS webhook_logs (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  webhook_id   INT UNSIGNED  NOT NULL,
  event_type   VARCHAR(60)   NOT NULL,
  payload      LONGTEXT,
  response_code SMALLINT UNSIGNED,
  response_body TEXT,
  duration_ms  SMALLINT UNSIGNED,
  success      TINYINT(1)    DEFAULT 0,
  fired_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_wl_webhook (webhook_id),
  FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Social SSO settings
INSERT IGNORE INTO settings (`key`, value, type, label, group_name) VALUES
('google_client_id',     '', 'text',    'Google Client ID',     'social_login'),
('google_client_secret', '', 'text',    'Google Client Secret', 'social_login'),
('github_client_id',     '', 'text',    'GitHub Client ID',     'social_login'),
('github_client_secret', '', 'text',    'GitHub Client Secret', 'social_login'),
('google_sso_enabled',   '0','boolean', 'Enable Google SSO',    'social_login'),
('github_sso_enabled',   '0','boolean', 'Enable GitHub SSO',    'social_login'),
('slack_webhook_url',    '', 'text',    'Slack Webhook URL',    'general'),
('slack_notifications',  '0','boolean', 'Slack Notifications',  'general');
