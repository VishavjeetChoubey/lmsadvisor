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
