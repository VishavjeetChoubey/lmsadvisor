-- Migration 0025: Multi-tenant / White-label + Corporate Training + API Marketplace

-- ── Tenants (white-label clients) ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tenants (
    id             INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    uuid           CHAR(36)        NOT NULL UNIQUE,
    name           VARCHAR(120)    NOT NULL,
    slug           VARCHAR(80)     NOT NULL UNIQUE,   -- used in subdomain
    custom_domain  VARCHAR(255),                      -- e.g. learn.client.com
    status         ENUM('active','suspended','trial') DEFAULT 'trial',
    plan           ENUM('trial','starter','pro','enterprise') DEFAULT 'trial',
    logo_url       VARCHAR(500),
    favicon_url    VARCHAR(500),
    primary_color  CHAR(7)         DEFAULT '#5b5ef6',
    accent_color   CHAR(7)         DEFAULT '#3b82f6',
    email_from     VARCHAR(255),
    email_name     VARCHAR(120),
    custom_css     TEXT,
    custom_js      TEXT,
    seat_limit     SMALLINT UNSIGNED DEFAULT 100,
    storage_gb     SMALLINT UNSIGNED DEFAULT 5,
    trial_ends_at  TIMESTAMP       NULL,
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_slug   (slug),
    KEY idx_domain (custom_domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link users to tenants
ALTER TABLE users ADD COLUMN IF NOT EXISTS tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE users ADD KEY IF NOT EXISTS idx_user_tenant (tenant_id);

-- Link courses to tenants
ALTER TABLE courses ADD COLUMN IF NOT EXISTS tenant_id INT UNSIGNED NULL AFTER id;
ALTER TABLE courses ADD KEY IF NOT EXISTS idx_course_tenant (tenant_id);

-- Tenant admin users
CREATE TABLE IF NOT EXISTS tenant_admins (
    id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id  INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    role       ENUM('owner','admin') DEFAULT 'admin',
    created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ta (tenant_id, user_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Corporate Training ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS organisations (
    id            INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    uuid          CHAR(36)        NOT NULL UNIQUE,
    name          VARCHAR(120)    NOT NULL,
    domain        VARCHAR(255),
    seat_limit    SMALLINT UNSIGNED DEFAULT 50,
    seats_used    SMALLINT UNSIGNED DEFAULT 0,
    billing_email VARCHAR(255),
    status        ENUM('active','suspended') DEFAULT 'active',
    created_at    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organisation_members (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organisation_id INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    role            ENUM('manager','employee') DEFAULT 'employee',
    department      VARCHAR(120),
    joined_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_om (organisation_id, user_id),
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)         REFERENCES users(id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mandatory course assignments by manager
CREATE TABLE IF NOT EXISTS course_assignments (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organisation_id INT UNSIGNED NOT NULL,
    course_id       INT UNSIGNED NOT NULL,
    assigned_by     INT UNSIGNED NOT NULL,
    due_date        DATE         NULL,
    is_mandatory    TINYINT(1)   DEFAULT 1,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ca (organisation_id, course_id),
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id)       REFERENCES courses(id)       ON DELETE CASCADE,
    FOREIGN KEY (assigned_by)     REFERENCES users(id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── API Marketplace ──────────────────────────────────────────────────────────
-- Enhance api_tokens with marketplace fields (from migration 0007)

ALTER TABLE api_tokens ADD COLUMN IF NOT EXISTS app_name    VARCHAR(120) NULL AFTER name;
ALTER TABLE api_tokens ADD COLUMN IF NOT EXISTS app_url     VARCHAR(500) NULL AFTER app_name;
ALTER TABLE api_tokens ADD COLUMN IF NOT EXISTS app_desc    TEXT         NULL AFTER app_url;
ALTER TABLE api_tokens ADD COLUMN IF NOT EXISTS is_public   TINYINT(1)   DEFAULT 0 AFTER app_desc;
ALTER TABLE api_tokens ADD COLUMN IF NOT EXISTS rate_limit  SMALLINT UNSIGNED DEFAULT 1000 AFTER is_public;
ALTER TABLE api_tokens ADD COLUMN IF NOT EXISTS calls_today INT UNSIGNED  DEFAULT 0 AFTER rate_limit;
ALTER TABLE api_tokens ADD COLUMN IF NOT EXISTS calls_total BIGINT UNSIGNED DEFAULT 0 AFTER calls_today;
ALTER TABLE api_tokens ADD COLUMN IF NOT EXISTS last_used_at TIMESTAMP    NULL AFTER calls_total;

-- Webhook event log
CREATE TABLE IF NOT EXISTS webhook_deliveries (
    id          BIGINT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
    webhook_id  INT UNSIGNED     NOT NULL,
    event       VARCHAR(60)      NOT NULL,
    payload     JSON,
    status      ENUM('pending','delivered','failed') DEFAULT 'pending',
    http_code   SMALLINT UNSIGNED,
    attempts    TINYINT UNSIGNED DEFAULT 0,
    delivered_at TIMESTAMP       NULL,
    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wd_webhook (webhook_id),
    KEY idx_wd_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Instructor marketplace applications
CREATE TABLE IF NOT EXISTS instructor_applications (
    id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id      INT UNSIGNED NOT NULL UNIQUE,
    bio          TEXT,
    expertise    VARCHAR(500),
    portfolio_url VARCHAR(500),
    status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    revenue_pct  TINYINT UNSIGNED DEFAULT 70,   -- instructor gets 70%
    applied_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    reviewed_at  TIMESTAMP    NULL,
    reviewed_by  INT UNSIGNED NULL,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
