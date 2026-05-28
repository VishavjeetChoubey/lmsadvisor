-- Migration 0022: Add uuid to enrollments for WooCommerce plugin
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id;

-- Back-fill existing enrollments with UUIDs
UPDATE enrollments SET uuid = UUID() WHERE uuid IS NULL;

-- Make it unique and not null
ALTER TABLE enrollments MODIFY COLUMN uuid CHAR(36) NOT NULL;
ALTER TABLE enrollments ADD UNIQUE KEY uq_enrollment_uuid (uuid);
