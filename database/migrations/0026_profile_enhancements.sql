-- Migration 0026: Profile photos, password reset, email verification, lesson gating

-- Profile photo on users
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo    VARCHAR(500) NULL AFTER last_name;
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio              TEXT         NULL AFTER profile_photo;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone            VARCHAR(30)  NULL AFTER bio;
ALTER TABLE users ADD COLUMN IF NOT EXISTS timezone         VARCHAR(60)  DEFAULT 'UTC' AFTER phone;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified   TINYINT(1)   DEFAULT 0 AFTER timezone;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at    TIMESTAMP    NULL AFTER email_verified;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    email      VARCHAR(255)    NOT NULL,
    token      CHAR(64)        NOT NULL UNIQUE,
    expires_at TIMESTAMP       NOT NULL,
    used       TINYINT(1)      DEFAULT 0,
    created_at TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pr_email (email),
    KEY idx_pr_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email verification tokens
CREATE TABLE IF NOT EXISTS email_verifications (
    id         INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    user_id    INT UNSIGNED    NOT NULL,
    token      CHAR(64)        NOT NULL UNIQUE,
    expires_at TIMESTAMP       NOT NULL,
    created_at TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_ev_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lesson gating rules
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS is_locked          TINYINT(1)   DEFAULT 0 AFTER is_mandatory;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS unlock_after_lesson INT UNSIGNED NULL AFTER is_locked;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS min_time_sec       INT UNSIGNED DEFAULT 0 AFTER unlock_after_lesson;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS available_from     TIMESTAMP    NULL AFTER min_time_sec;

-- Drip schedule (days after enrollment before lesson unlocks)
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS drip_days INT UNSIGNED DEFAULT 0 AFTER available_from;

-- Search index hints
ALTER TABLE courses  ADD FULLTEXT INDEX IF NOT EXISTS ft_courses  (title, short_description);
ALTER TABLE users    ADD FULLTEXT INDEX IF NOT EXISTS ft_users    (first_name, last_name, email);
ALTER TABLE lessons  ADD FULLTEXT INDEX IF NOT EXISTS ft_lessons  (title);
