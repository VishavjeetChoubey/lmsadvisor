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
