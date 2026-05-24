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
