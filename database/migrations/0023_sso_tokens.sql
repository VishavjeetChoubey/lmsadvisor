-- Migration 0023: SSO tokens for WooCommerce → LMS auto-login

CREATE TABLE IF NOT EXISTS sso_tokens (
    id            INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    user_id       INT UNSIGNED    NOT NULL,
    token         VARCHAR(64)     NOT NULL UNIQUE,
    redirect_path VARCHAR(300)    NOT NULL DEFAULT '/learn/dashboard',
    expires_at    TIMESTAMP       NOT NULL,
    used          TINYINT(1)      NOT NULL DEFAULT 0,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_token   (token),
    KEY idx_expires (expires_at),
    KEY idx_user    (user_id),
    CONSTRAINT fk_sso_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
