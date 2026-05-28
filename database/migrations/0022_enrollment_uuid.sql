-- Migration 0022: Add uuid to enrollments for WooCommerce plugin

-- Add column only if not exists
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id;

-- Back-fill existing enrollments with UUIDs
UPDATE enrollments SET uuid = UUID() WHERE uuid IS NULL;

-- Make not null
ALTER TABLE enrollments MODIFY COLUMN uuid CHAR(36) NOT NULL DEFAULT '';

-- Add unique key only if it doesn't exist
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'enrollments' AND index_name = 'uq_enrollment_uuid');
SET @sql := IF(@exist = 0,
    'ALTER TABLE enrollments ADD UNIQUE KEY uq_enrollment_uuid (uuid)',
    'SELECT ''Key already exists, skipping''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
