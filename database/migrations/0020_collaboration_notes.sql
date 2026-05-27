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
