-- Seed 001: Default Super Admin
-- Email:    admin@lmsadvisor.local
-- Password: Admin@1234
-- Change password after first login via Phase 3 User Management.

SET NAMES utf8mb4;

INSERT IGNORE INTO users
  (uuid, role_id, first_name, last_name, email, password_hash, is_active, email_verified_at)
VALUES (
  '00000000-0000-0000-0000-000000000001',
  1,
  'Super',
  'Admin',
  'admin@lmsadvisor.local',
  '$2y$12$EqUXz/rdyGIJt8weCIUJbeJ8wTFX8vyQUIXMa5OHT5tJbZXo2NxxW',
  1,
  NOW()
);
