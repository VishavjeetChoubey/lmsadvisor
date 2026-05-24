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
