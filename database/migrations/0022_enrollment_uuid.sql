-- Migration 0022: Add uuid to enrollments for WooCommerce plugin

ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id;

UPDATE enrollments SET uuid = UUID() WHERE uuid IS NULL OR uuid = '';

ALTER TABLE enrollments MODIFY COLUMN uuid CHAR(36) NOT NULL DEFAULT '';

ALTER TABLE enrollments ADD UNIQUE KEY uq_enrollment_uuid (uuid);
