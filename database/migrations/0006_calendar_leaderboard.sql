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
