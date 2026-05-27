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
