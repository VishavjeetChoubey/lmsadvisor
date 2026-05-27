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
