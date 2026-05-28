-- ═══════════════════════════════════════════════════════════════════
-- LMSAdvisor — Master Schema (v3.0)
-- Single-file install: all tables, all columns, safe to re-run
-- Generated from 23 migration files
-- ═══════════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ────────────────────────────────────────────────────────────
-- 0001_roles_users.sql
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- 0002_courses_sections_lessons.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0002: Categories, Courses, Sections, Lessons
-- Requires 0001

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS categories (
  id        INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name      VARCHAR(120) NOT NULL,
  slug      VARCHAR(120) NOT NULL UNIQUE,
  parent_id INT UNSIGNED NULL,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courses (
  id                  INT UNSIGNED     PRIMARY KEY AUTO_INCREMENT,
  uuid                CHAR(36)         NOT NULL UNIQUE,
  title               VARCHAR(255)     NOT NULL,
  slug                VARCHAR(255)     NOT NULL UNIQUE,
  description         LONGTEXT,
  short_description   TEXT,
  thumbnail           VARCHAR(255),
  preview_video       VARCHAR(500),
  category_id         INT UNSIGNED,
  level               ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
  language            VARCHAR(50)      DEFAULT 'English',
  is_rtl              TINYINT(1)       DEFAULT 0,
  status              ENUM('draft','published','archived') DEFAULT 'draft',
  visibility          ENUM('public','private','password')  DEFAULT 'public',
  password            VARCHAR(255),
  enrollment_type     ENUM('admin_only','open') DEFAULT 'admin_only',
  pass_percentage     TINYINT UNSIGNED DEFAULT 80,
  certificate_enabled TINYINT(1)       DEFAULT 1,
  forum_enabled       TINYINT(1)       DEFAULT 0,
  forum_enrolled_only TINYINT(1)       DEFAULT 1,
  drip_enabled        TINYINT(1)       DEFAULT 0,
  end_date            DATE             NULL,
  grade_points        INT UNSIGNED     DEFAULT 0,
  duration_hours      DECIMAL(6,2),
  sort_order          INT UNSIGNED     DEFAULT 0,
  created_by          INT UNSIGNED     NOT NULL,
  published_at        TIMESTAMP        NULL,
  created_at          TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by)  REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_managers (
  course_id   INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  role        ENUM('instructor','manager') DEFAULT 'instructor',
  assigned_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (course_id, user_id),
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_materials (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  course_id   INT UNSIGNED  NOT NULL,
  title       VARCHAR(255)  NOT NULL,
  file_path   VARCHAR(500)  NOT NULL,
  file_type   VARCHAR(80),
  sort_order  INT UNSIGNED  DEFAULT 0,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_reviews (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  course_id   INT UNSIGNED  NOT NULL,
  user_id     INT UNSIGNED  NOT NULL,
  rating      TINYINT UNSIGNED NOT NULL,
  review      TEXT,
  is_approved TINYINT(1)    DEFAULT 0,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review (course_id, user_id),
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sections (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  uuid        CHAR(36)     NOT NULL UNIQUE,
  course_id   INT UNSIGNED NOT NULL,
  title       VARCHAR(255) NOT NULL,
  description LONGTEXT,
  drip_days   INT UNSIGNED NULL,
  sort_order  INT UNSIGNED DEFAULT 0,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lessons (
  id             INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  uuid           CHAR(36)      NOT NULL UNIQUE,
  section_id     INT UNSIGNED  NOT NULL,
  course_id      INT UNSIGNED  NOT NULL,
  title          VARCHAR(255)  NOT NULL,
  type           ENUM('text','video','document','scorm','quiz') NOT NULL,
  video_type     ENUM('upload','youtube','vimeo') NULL,
  content        LONGTEXT,
  file_path      VARCHAR(500),
  thumbnail      VARCHAR(255),
  duration_sec   INT UNSIGNED,
  drip_days      INT UNSIGNED  NULL,
  is_previewable TINYINT(1)    DEFAULT 0,
  is_mandatory   TINYINT(1)    DEFAULT 1,
  sort_order     INT UNSIGNED  DEFAULT 0,
  created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────
-- 0003_quizzes_enrollments_progress.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0003: Quizzes, Enrollments, Progress, Certificates
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS quizzes (
  id                 INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  lesson_id          INT UNSIGNED  NOT NULL UNIQUE,
  title              VARCHAR(255)  NOT NULL,
  description        TEXT,
  time_limit_sec     INT UNSIGNED  NULL,
  pass_percentage    TINYINT UNSIGNED DEFAULT 70,
  shuffle_questions  TINYINT(1)    DEFAULT 0,
  shuffle_options    TINYINT(1)    DEFAULT 0,
  show_answers_after TINYINT(1)    DEFAULT 1,
  max_attempts       TINYINT UNSIGNED DEFAULT 3,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  quiz_id     INT UNSIGNED  NOT NULL,
  question    LONGTEXT      NOT NULL,
  explanation LONGTEXT,
  type        ENUM('single','multiple','true_false','fill_blank') DEFAULT 'single',
  points      TINYINT UNSIGNED DEFAULT 1,
  sort_order  INT UNSIGNED  DEFAULT 0,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_options (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  question_id INT UNSIGNED  NOT NULL,
  option_text TEXT          NOT NULL,
  is_correct  TINYINT(1)    DEFAULT 0,
  sort_order  INT UNSIGNED  DEFAULT 0,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrollments (
  id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  course_id    INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  enrolled_by  INT UNSIGNED NULL,
  status       ENUM('active','completed','suspended','expired') DEFAULT 'active',
  enrolled_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP    NULL,
  expires_at   TIMESTAMP    NULL,
  UNIQUE KEY uq_enrollment (course_id, user_id),
  FOREIGN KEY (course_id)   REFERENCES courses(id)  ON DELETE CASCADE,
  FOREIGN KEY (user_id)     REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (enrolled_by) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_progress (
  id             INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  enrollment_id  INT UNSIGNED NOT NULL,
  lesson_id      INT UNSIGNED NOT NULL,
  user_id        INT UNSIGNED NOT NULL,
  status         ENUM('not_started','in_progress','completed') DEFAULT 'not_started',
  progress_pct   TINYINT UNSIGNED DEFAULT 0,
  scorm_data     JSON,
  time_spent_sec INT UNSIGNED DEFAULT 0,
  started_at     TIMESTAMP    NULL,
  completed_at   TIMESTAMP    NULL,
  last_accessed  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_lp (enrollment_id, lesson_id),
  FOREIGN KEY (enrollment_id) REFERENCES enrollments(id)  ON DELETE CASCADE,
  FOREIGN KEY (lesson_id)     REFERENCES lessons(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempts (
  id             INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  quiz_id        INT UNSIGNED  NOT NULL,
  user_id        INT UNSIGNED  NOT NULL,
  enrollment_id  INT UNSIGNED  NOT NULL,
  score          DECIMAL(5,2)  NULL,
  passed         TINYINT(1)    NULL,
  time_taken_sec INT UNSIGNED  NULL,
  answers        JSON,
  started_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  completed_at   TIMESTAMP     NULL,
  FOREIGN KEY (quiz_id)       REFERENCES quizzes(id)     ON DELETE CASCADE,
  FOREIGN KEY (user_id)       REFERENCES users(id)        ON DELETE CASCADE,
  FOREIGN KEY (enrollment_id) REFERENCES enrollments(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS certificates (
  id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  uuid          CHAR(36)     NOT NULL UNIQUE,
  enrollment_id INT UNSIGNED NOT NULL UNIQUE,
  user_id       INT UNSIGNED NOT NULL,
  course_id     INT UNSIGNED NOT NULL,
  file_path     VARCHAR(500),
  issued_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────
-- 0004_settings_sessions.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0004: Settings table + default seed data
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `key`      VARCHAR(120) NOT NULL UNIQUE,
  value      LONGTEXT,
  type       ENUM('text','textarea','color','image','boolean','json','password') DEFAULT 'text',
  label      VARCHAR(191),
  group_name VARCHAR(80)  DEFAULT 'general',
  updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (`key`, value, type, label, group_name) VALUES
-- General
('site_name',            'LMSAdvisor',  'text',    'Site Name',                'general'),
('site_tagline',         '',            'text',    'Site Tagline',             'general'),
('site_logo',            '',            'image',   'Site Logo',                'general'),
('site_favicon',         '',            'image',   'Favicon',                  'general'),
('theme_color',          '#1a56db',     'color',   'Primary Color',            'general'),
('admin_email',          '',            'text',    'Admin Email',              'general'),
('timezone',             'UTC',         'text',    'Timezone',                 'general'),
('date_format',          'D M Y',       'text',    'Date Format',              'general'),
-- Security
('recaptcha_enabled',    '0',           'boolean', 'Enable reCAPTCHA',         'security'),
('recaptcha_site_key',   '',            'text',    'reCAPTCHA Site Key',       'security'),
('recaptcha_secret',     '',            'password','reCAPTCHA Secret Key',     'security'),
('login_max_attempts',   '5',           'text',    'Max Login Attempts',       'security'),
('login_lockout_min',    '15',          'text',    'Lockout Duration (min)',   'security'),
-- Email
('smtp_host',            '',            'text',    'SMTP Host',                'email'),
('smtp_port',            '587',         'text',    'SMTP Port',                'email'),
('smtp_user',            '',            'text',    'SMTP Username',            'email'),
('smtp_pass',            '',            'password','SMTP Password',            'email'),
('smtp_from',            '',            'text',    'From Address',             'email'),
('smtp_from_name',       'LMSAdvisor',  'text',    'From Name',                'email'),
('smtp_encryption',      'tls',         'text',    'Encryption (tls/ssl)',     'email'),
-- Certificates
('cert_signer_name',     '',            'text',    'Signatory Name',           'certificates'),
('cert_signer_title',    '',            'text',    'Signatory Title',          'certificates'),
('cert_footer_text',     '',            'textarea','Certificate Footer Text',  'certificates'),
('cert_template_color',  '#1a56db',     'color',   'Certificate Accent Color', 'certificates'),
-- Social Login
('social_google_enabled','0',           'boolean', 'Enable Google Login',      'social_login'),
('social_google_id',     '',            'text',    'Google Client ID',         'social_login'),
('social_google_secret', '',            'password','Google Client Secret',     'social_login'),
('social_github_enabled','0',           'boolean', 'Enable GitHub Login',      'social_login'),
('social_github_id',     '',            'text',    'GitHub Client ID',         'social_login'),
('social_github_secret', '',            'password','GitHub Client Secret',     'social_login'),
-- Webinar
('zoom_enabled',         '0',           'boolean', 'Enable Zoom Integration',  'webinar'),
('zoom_api_key',         '',            'text',    'Zoom API Key',             'webinar'),
('zoom_api_secret',      '',            'password','Zoom API Secret',          'webinar'),
('zoom_account_id',      '',            'text',    'Zoom Account ID',          'webinar'),
('gmeet_enabled',        '0',           'boolean', 'Enable Google Meet',       'webinar'),
('gmeet_oauth_json',     '',            'textarea','Google OAuth JSON',         'webinar'),
-- AI Integration
('ai_provider',          'openai',      'text',    'AI Provider (openai|anthropic)', 'ai'),
('ai_openai_key',        '',            'password','OpenAI API Key',           'ai'),
('ai_anthropic_key',     '',            'password','Anthropic (Claude) Key',   'ai'),
('ai_model',             'gpt-4o',      'text',    'Model Name',               'ai'),
('ai_enabled',           '0',           'boolean', 'Enable AI Features',       'ai'),
-- Reviews
('reviews_enabled',      '1',           'boolean', 'Enable Course Reviews',    'reviews'),
('reviews_auto_approve', '0',           'boolean', 'Auto-approve Reviews',     'reviews'),
-- Leaderboard
('leaderboard_enabled',  '1',           'boolean', 'Enable Leaderboard',       'leaderboard'),
('leaderboard_public',   '1',           'boolean', 'Public Leaderboard',       'leaderboard');

-- ────────────────────────────────────────────────────────────
-- 0005_forum_reviews_materials.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0005: Forum threads and replies
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS forum_threads (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  course_id   INT UNSIGNED  NOT NULL,
  user_id     INT UNSIGNED  NOT NULL,
  title       VARCHAR(255)  NOT NULL,
  body        LONGTEXT      NOT NULL,
  is_pinned   TINYINT(1)    DEFAULT 0,
  is_locked   TINYINT(1)    DEFAULT 0,
  reply_count INT UNSIGNED  DEFAULT 0,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_replies (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  thread_id   INT UNSIGNED  NOT NULL,
  user_id     INT UNSIGNED  NOT NULL,
  body        LONGTEXT      NOT NULL,
  is_solution TINYINT(1)    DEFAULT 0,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)   REFERENCES users(id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────
-- 0006_calendar_leaderboard.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0006: Calendar Events, Grade Points
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS course_calendar_events (
  id            INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  enrollment_id INT UNSIGNED  NOT NULL,
  user_id       INT UNSIGNED  NOT NULL,
  course_id     INT UNSIGNED  NOT NULL,
  title         VARCHAR(255)  NOT NULL,
  event_type    ENUM('enrollment','due_date','completion','webinar') DEFAULT 'enrollment',
  event_date    DATE          NOT NULL,
  notes         TEXT,
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
  INDEX idx_cal_user (user_id, event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grade_points (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  user_id     INT UNSIGNED  NOT NULL,
  course_id   INT UNSIGNED  NULL,
  points      INT           NOT NULL DEFAULT 0,
  reason      VARCHAR(191),
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
  INDEX idx_gp_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────
-- 0007_api_tokens_social_auth.sql
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- 0008_knowledge_base.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0008: Knowledge Base
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS kb_categories (
  id         INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  name       VARCHAR(120)  NOT NULL,
  slug       VARCHAR(120)  NOT NULL UNIQUE,
  sort_order INT UNSIGNED  DEFAULT 0,
  created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kb_articles (
  id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  uuid        CHAR(36)      NOT NULL UNIQUE,
  category_id INT UNSIGNED,
  title       VARCHAR(255)  NOT NULL,
  slug        VARCHAR(255)  NOT NULL UNIQUE,
  body        LONGTEXT      NOT NULL,
  status      ENUM('draft','published') DEFAULT 'draft',
  views       INT UNSIGNED  DEFAULT 0,
  created_by  INT UNSIGNED  NOT NULL,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES kb_categories(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by)  REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────
-- 0009_webinar_sessions.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0009: Webinar Sessions
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS webinar_sessions (
  id            INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  uuid          CHAR(36)      NOT NULL UNIQUE,
  course_id     INT UNSIGNED  NOT NULL,
  title         VARCHAR(255)  NOT NULL,
  provider      ENUM('zoom','google_meet') NOT NULL,
  meeting_id    VARCHAR(191),
  join_url      VARCHAR(500),
  start_url     VARCHAR(500),
  password      VARCHAR(100),
  scheduled_at  TIMESTAMP     NOT NULL,
  duration_min  INT UNSIGNED  DEFAULT 60,
  status        ENUM('scheduled','live','ended','cancelled') DEFAULT 'scheduled',
  created_by    INT UNSIGNED  NOT NULL,
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────
-- 0010_scorm_packages.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0010: Ensure scorm_data column exists in lesson_progress
-- (was already in 0003 but this is a safety migration)
ALTER TABLE lesson_progress
  MODIFY COLUMN scorm_data JSON NULL COMMENT 'SCORM 1.2/2004 CMI data blob';

-- Create scorm_packages directory tracking table (optional — for admin UI)
CREATE TABLE IF NOT EXISTS scorm_packages (
  id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lesson_id     INT UNSIGNED NOT NULL UNIQUE,
  entry_point   VARCHAR(500),
  scorm_version ENUM('1.2','2004') DEFAULT '1.2',
  extracted_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 0011_api_tokens_scopes.sql
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- 0012_custom_code_settings.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0012: Custom Code settings keys
INSERT IGNORE INTO settings (`key`, value, type, label, group_name) VALUES
('custom_css',       '', 'textarea', 'Custom CSS',        'custom_code'),
('custom_js_head',   '', 'textarea', 'Head JavaScript',   'custom_code'),
('custom_js_body',   '', 'textarea', 'Body JavaScript',   'custom_code'),
('custom_js_footer', '', 'textarea', 'Footer JavaScript', 'custom_code');

-- ────────────────────────────────────────────────────────────
-- 0013_leaderboard_group_fix.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0013: Move leaderboard settings to 'reviews' group
-- They display on the same Settings tab as Reviews, so must share the group name
UPDATE settings SET group_name = 'reviews' 
WHERE `key` IN ('leaderboard_enabled', 'leaderboard_public')
  AND group_name = 'leaderboard';

-- ────────────────────────────────────────────────────────────
-- 0014_analytics.sql
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- 0015_email_system.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0015: Email notification system

CREATE TABLE IF NOT EXISTS email_queue (
  id           INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  user_id      INT UNSIGNED,
  to_email     VARCHAR(255)  NOT NULL,
  to_name      VARCHAR(120),
  subject      VARCHAR(255)  NOT NULL,
  body_html    LONGTEXT      NOT NULL,
  template     VARCHAR(60),
  status       ENUM('pending','sent','failed') DEFAULT 'pending',
  attempts     TINYINT UNSIGNED DEFAULT 0,
  error_msg    VARCHAR(500),
  scheduled_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  sent_at      TIMESTAMP     NULL,
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_eq_status (status),
  INDEX idx_eq_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_templates (
  id         INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  slug       VARCHAR(60)   NOT NULL UNIQUE,
  name       VARCHAR(120)  NOT NULL,
  subject    VARCHAR(255)  NOT NULL,
  body_html  LONGTEXT      NOT NULL,
  variables  VARCHAR(500),
  is_enabled TINYINT(1)    DEFAULT 1,
  updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_unsubscribes (
  id         INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  email      VARCHAR(255)  NOT NULL UNIQUE,
  token      CHAR(64)      NOT NULL UNIQUE,
  created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default email templates (plain HTML placeholders — edit in Admin > Email)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'enrollment_confirmation',
  'Enrollment Confirmation',
  'You are enrolled in: {{course_title}}',
  '<p>Hi {{student_name}}, you are enrolled in <strong>{{course_title}}</strong>. <a href="{{course_url}}">Start Learning</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","course_title","course_level","course_duration","grade_points","course_url","site_name","site_logo","unsubscribe_url"]'
);

INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'course_completion',
  'Course Completion',
  'Congratulations! You completed: {{course_title}}',
  '<p>Hi {{student_name}}, congratulations on completing <strong>{{course_title}}</strong>! You earned <strong>{{grade_points}} grade points</strong>. <a href="{{certificate_url}}">Download Certificate</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","course_title","grade_points","certificate_url","course_url","site_name","unsubscribe_url"]'
);

INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'quiz_result',
  'Quiz Result',
  'Quiz Result: {{quiz_title}} - {{result}}',
  '<p>Hi {{student_name}}, your result for <strong>{{quiz_title}}</strong> in {{course_title}}: <strong>{{score}}%</strong> (pass mark: {{pass_percentage}}%). Result: {{result}}</p><p><a href="{{course_url}}">Continue Learning</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","quiz_title","course_title","score","pass_percentage","result","result_emoji","result_color","course_url","site_name","unsubscribe_url"]'
);

INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'webinar_reminder',
  'Webinar Reminder',
  'Reminder: {{webinar_title}} starts in 24 hours',
  '<p>Hi {{student_name}}, your webinar <strong>{{webinar_title}}</strong> starts in 24 hours. Date: {{webinar_date}} at {{webinar_time}} ({{webinar_duration}} min) via {{webinar_provider}}.</p><p><a href="{{join_url}}">Join Webinar</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","webinar_title","webinar_date","webinar_time","webinar_duration","webinar_provider","join_url","site_name","unsubscribe_url"]'
);

INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'certificate_ready',
  'Certificate Ready',
  'Your certificate for {{course_title}} is ready!',
  '<p>Hi {{student_name}}, your certificate for <strong>{{course_title}}</strong> is ready. <a href="{{certificate_url}}">View Certificate</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","course_title","certificate_url","site_name","unsubscribe_url"]'
);

-- ────────────────────────────────────────────────────────────
-- 0016_learning_paths.sql
-- ────────────────────────────────────────────────────────────
-- Phase 21: Learning Paths & Prerequisites
CREATE TABLE IF NOT EXISTS learning_paths (
  id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  uuid         CHAR(36)     NOT NULL UNIQUE,
  title        VARCHAR(255) NOT NULL,
  slug         VARCHAR(255) NOT NULL UNIQUE,
  description  TEXT,
  thumbnail    VARCHAR(255),
  is_published TINYINT(1)   DEFAULT 0,
  sort_order   INT UNSIGNED DEFAULT 0,
  created_by   INT UNSIGNED,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS learning_path_courses (
  id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  path_id      INT UNSIGNED NOT NULL,
  course_id    INT UNSIGNED NOT NULL,
  sort_order   INT UNSIGNED DEFAULT 0,
  is_required  TINYINT(1)   DEFAULT 1,
  UNIQUE KEY unique_path_course (path_id, course_id),
  FOREIGN KEY (path_id)   REFERENCES learning_paths(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS learning_path_enrollments (
  id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  path_id      INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  status       ENUM('active','completed') DEFAULT 'active',
  enrolled_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP    NULL,
  UNIQUE KEY unique_path_enrollment (path_id, user_id),
  FOREIGN KEY (path_id)  REFERENCES learning_paths(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prerequisites: course_id requires prerequisite_course_id to be completed first
CREATE TABLE IF NOT EXISTS course_prerequisites (
  id                    INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  course_id             INT UNSIGNED NOT NULL,
  prerequisite_course_id INT UNSIGNED NOT NULL,
  UNIQUE KEY unique_prereq (course_id, prerequisite_course_id),
  FOREIGN KEY (course_id)              REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (prerequisite_course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 0017_groups_cohorts.sql
-- ────────────────────────────────────────────────────────────
-- Phase 22: Groups & Cohorts
CREATE TABLE IF NOT EXISTS user_groups (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  uuid        CHAR(36)     NOT NULL UNIQUE,
  name        VARCHAR(255) NOT NULL,
  description TEXT,
  manager_id  INT UNSIGNED COMMENT 'User who manages this group',
  created_by  INT UNSIGNED,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_group_members (
  group_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  joined_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id, user_id),
  FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_group_courses (
  group_id  INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  assigned_at TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id, course_id),
  FOREIGN KEY (group_id)  REFERENCES user_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 0018_gamification.sql
-- ────────────────────────────────────────────────────────────
-- Phase 24: Gamification Engine
CREATE TABLE IF NOT EXISTS badges (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  uuid        CHAR(36)     NOT NULL UNIQUE,
  name        VARCHAR(120) NOT NULL,
  description TEXT,
  icon        VARCHAR(60)  DEFAULT 'bi-award-fill' COMMENT 'Bootstrap Icon class',
  color       VARCHAR(20)  DEFAULT '#5b5ef6',
  rule_type   ENUM('courses_completed','quiz_score','login_streak','grade_points','manual') NOT NULL,
  rule_value  INT UNSIGNED DEFAULT 0 COMMENT 'e.g. 5 courses, 100% score, 7-day streak',
  is_active   TINYINT(1)   DEFAULT 1,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_badges (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  badge_id    INT UNSIGNED NOT NULL,
  awarded_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_badge (user_id, badge_id),
  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (badge_id) REFERENCES badges(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_streaks (
  user_id      INT UNSIGNED PRIMARY KEY,
  current_days INT UNSIGNED DEFAULT 0,
  longest_days INT UNSIGNED DEFAULT 0,
  last_login   DATE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Levels config
INSERT IGNORE INTO settings (`key`, value, type, label, group_name) VALUES
('gamification_enabled', '1',  'boolean', 'Enable Gamification', 'general'),
('level_bronze_pts',  '100',  'text', 'Bronze Level Points',   'general'),
('level_silver_pts',  '500',  'text', 'Silver Level Points',   'general'),
('level_gold_pts',    '1500', 'text', 'Gold Level Points',     'general'),
('level_platinum_pts','5000', 'text', 'Platinum Level Points', 'general');

-- Seed default badges
INSERT IGNORE INTO badges (uuid, name, description, icon, color, rule_type, rule_value) VALUES
(UUID(), 'First Step',     'Complete your first course',            'bi-mortarboard-fill', '#5b5ef6', 'courses_completed', 1),
(UUID(), 'On A Roll',      'Complete 5 courses',                    'bi-fire',             '#f59e0b', 'courses_completed', 5),
(UUID(), 'Course Master',  'Complete 10 courses',                   'bi-trophy-fill',      '#d97706', 'courses_completed', 10),
(UUID(), 'Perfect Score',  'Score 100% on any quiz',                'bi-star-fill',        '#059669', 'quiz_score',        100),
(UUID(), 'Week Warrior',   'Login 7 days in a row',                 'bi-calendar-check',   '#2563eb', 'login_streak',      7),
(UUID(), 'Month Streak',   'Login 30 days in a row',                'bi-lightning-fill',   '#dc2626', 'login_streak',      30),
(UUID(), 'Point Collector','Earn 1000 grade points',                'bi-gem',              '#7c3aed', 'grade_points',      1000),
(UUID(), 'Elite Learner',  'Earn 5000 grade points',               'bi-award-fill',       '#b45309', 'grade_points',      5000);

-- ────────────────────────────────────────────────────────────
-- 0019_assignments.sql
-- ────────────────────────────────────────────────────────────
-- Phase 25: Assignments & Submissions
CREATE TABLE IF NOT EXISTS assignments (
  id             INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lesson_id      INT UNSIGNED NOT NULL UNIQUE,
  title          VARCHAR(255) NOT NULL,
  brief          LONGTEXT     COMMENT 'Assignment instructions (HTML)',
  rubric         TEXT         COMMENT 'Grading criteria',
  deadline       TIMESTAMP    NULL,
  max_score      TINYINT UNSIGNED DEFAULT 100,
  pass_score     TINYINT UNSIGNED DEFAULT 50,
  max_attempts   TINYINT UNSIGNED DEFAULT 3,
  allowed_types  VARCHAR(200) DEFAULT 'pdf,zip,doc,docx,jpg,png' COMMENT 'comma-sep extensions',
  max_file_mb    TINYINT UNSIGNED DEFAULT 20,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assignment_submissions (
  id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  assignment_id INT UNSIGNED NOT NULL,
  enrollment_id INT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NOT NULL,
  attempt       TINYINT UNSIGNED DEFAULT 1,
  file_path     VARCHAR(500),
  file_name     VARCHAR(255),
  file_size     INT UNSIGNED,
  file_hash     CHAR(64)     COMMENT 'SHA-256 for plagiarism detection',
  comment       TEXT         COMMENT 'Student submission note',
  score         TINYINT UNSIGNED NULL,
  feedback      TEXT         COMMENT 'Instructor feedback',
  status        ENUM('submitted','graded','resubmit','pass','fail') DEFAULT 'submitted',
  submitted_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  graded_at     TIMESTAMP    NULL,
  graded_by     INT UNSIGNED,
  FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 0020_collaboration_notes.sql
-- ────────────────────────────────────────────────────────────
-- Phase 23: Live Collaboration & Q&A

CREATE TABLE IF NOT EXISTS lesson_notes (
  id           INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  user_id      INT UNSIGNED  NOT NULL,
  lesson_id    INT UNSIGNED  NOT NULL,
  course_id    INT UNSIGNED  NOT NULL,
  note         TEXT          NOT NULL,
  timestamp_sec INT UNSIGNED DEFAULT 0 COMMENT 'Video position when note was taken',
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ln_user_lesson (user_id, lesson_id),
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_comments (
  id           INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  lesson_id    INT UNSIGNED  NOT NULL,
  user_id      INT UNSIGNED  NOT NULL,
  parent_id    INT UNSIGNED  NULL COMMENT 'For threaded replies',
  body         TEXT          NOT NULL,
  is_pinned    TINYINT(1)    DEFAULT 0,
  is_approved  TINYINT(1)    DEFAULT 1,
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lc_lesson (lesson_id),
  FOREIGN KEY (lesson_id)  REFERENCES lessons(id)  ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id)     ON DELETE CASCADE,
  FOREIGN KEY (parent_id)  REFERENCES lesson_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Phase 20: Advanced Analytics - learner-specific tables
CREATE TABLE IF NOT EXISTS lesson_time_log (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id      INT UNSIGNED  NOT NULL,
  lesson_id    INT UNSIGNED  NOT NULL,
  seconds      INT UNSIGNED  DEFAULT 0,
  logged_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ltl_user (user_id),
  INDEX idx_ltl_lesson (lesson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grade book view support
CREATE TABLE IF NOT EXISTS grade_book (
  id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  enrollment_id INT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NOT NULL,
  course_id     INT UNSIGNED NOT NULL,
  item_type     ENUM('quiz','assignment','course') NOT NULL,
  item_id       INT UNSIGNED NOT NULL,
  item_title    VARCHAR(255),
  score         DECIMAL(5,2) DEFAULT 0,
  max_score     DECIMAL(5,2) DEFAULT 100,
  weight        DECIMAL(4,2) DEFAULT 1.00,
  graded_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gb_enrollment (enrollment_id),
  INDEX idx_gb_user_course (user_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add lesson_id to forum_threads for "Ask a question" linking
ALTER TABLE forum_threads ADD COLUMN IF NOT EXISTS lesson_id INT UNSIGNED NULL AFTER course_id;

-- ────────────────────────────────────────────────────────────
-- 0021_ai_tutor.sql
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- 0022_enrollment_uuid.sql
-- ────────────────────────────────────────────────────────────
-- Migration 0022: Add uuid to enrollments for WooCommerce plugin

-- Add column only if not exists
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id;

-- Back-fill existing enrollments with UUIDs
UPDATE enrollments SET uuid = UUID() WHERE uuid IS NULL;

-- Make not null
ALTER TABLE enrollments MODIFY COLUMN uuid CHAR(36) NOT NULL DEFAULT '';

-- Add unique key only if it doesn't exist
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'enrollments' AND index_name = 'uq_enrollment_uuid');
SET @sql := IF(@exist = 0,
    'ALTER TABLE enrollments ADD UNIQUE KEY uq_enrollment_uuid (uuid)',
    'SELECT ''Key already exists, skipping''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- 0023_sso_tokens.sql
-- ────────────────────────────────────────────────────────────
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


SET FOREIGN_KEY_CHECKS = 1;