-- Migration 0029: Add 'assignment' to lessons.type ENUM
ALTER TABLE lessons MODIFY COLUMN type
  ENUM('text','video','document','scorm','quiz','assignment') NOT NULL;
