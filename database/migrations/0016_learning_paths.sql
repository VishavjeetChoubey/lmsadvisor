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
