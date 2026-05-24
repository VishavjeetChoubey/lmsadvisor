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
