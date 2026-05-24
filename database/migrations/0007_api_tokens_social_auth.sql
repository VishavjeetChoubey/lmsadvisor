-- Migration 0007: API Tokens, Notifications
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS api_tokens (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  user_id     INT UNSIGNED  NOT NULL,
  token       CHAR(32)      NOT NULL UNIQUE,
  name        VARCHAR(120),
  last_used   TIMESTAMP     NULL,
  expires_at  TIMESTAMP     NULL,
  is_active   TINYINT(1)    DEFAULT 1,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  type       VARCHAR(80)  NOT NULL,
  title      VARCHAR(255) NOT NULL,
  body       TEXT,
  data       JSON,
  is_read    TINYINT(1)   DEFAULT 0,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
