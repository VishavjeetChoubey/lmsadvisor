-- Migration 0001: Roles, Users, Social Accounts, Sessions, Audit Log
-- Run this FIRST. All other migrations depend on these tables.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS roles (
  id           TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name         VARCHAR(30)  NOT NULL UNIQUE,
  display_name VARCHAR(50)  NOT NULL,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (name, display_name) VALUES
  ('super_admin', 'Super Administrator'),
  ('admin',       'Administrator'),
  ('manager',     'Manager'),
  ('student',     'Student');

CREATE TABLE IF NOT EXISTS users (
  id                INT UNSIGNED     PRIMARY KEY AUTO_INCREMENT,
  uuid              CHAR(36)         NOT NULL UNIQUE,
  role_id           TINYINT UNSIGNED NOT NULL,
  first_name        VARCHAR(80)      NOT NULL,
  last_name         VARCHAR(80)      NOT NULL,
  email             VARCHAR(191)     NOT NULL UNIQUE,
  password_hash     VARCHAR(255),
  avatar            VARCHAR(255),
  is_active         TINYINT(1)       DEFAULT 1,
  email_verified_at TIMESTAMP        NULL,
  last_login_at     TIMESTAMP        NULL,
  login_attempts    TINYINT UNSIGNED DEFAULT 0,
  locked_until      TIMESTAMP        NULL,
  created_at        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_accounts (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  user_id     INT UNSIGNED  NOT NULL,
  provider    VARCHAR(30)   NOT NULL,
  provider_id VARCHAR(191)  NOT NULL,
  access_token TEXT,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_social (provider, provider_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS session_history (
  id             BIGINT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  user_id        INT UNSIGNED     NOT NULL,
  session_token  VARCHAR(255)     NOT NULL,
  ip_address     VARCHAR(45),
  user_agent     TEXT,
  device_type    ENUM('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
  browser        VARCHAR(100),
  os             VARCHAR(100),
  login_at       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  logout_at      TIMESTAMP        NULL,
  last_active    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  duration_sec   INT UNSIGNED     DEFAULT 0,
  is_active      TINYINT(1)       DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     INT UNSIGNED,
  action      VARCHAR(120)    NOT NULL,
  entity_type VARCHAR(80),
  entity_id   INT UNSIGNED,
  old_value   JSON,
  new_value   JSON,
  ip_address  VARCHAR(45),
  user_agent  TEXT,
  created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_action (action),
  INDEX idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
