-- Migration 0030: Ensure smtp_enabled and all smtp settings have correct group_name

-- Insert if missing
INSERT IGNORE INTO settings (`key`, value, type, label, group_name) VALUES
('smtp_enabled',    '0',    'boolean', 'Enable SMTP',          'email'),
('smtp_from_email', '',     'text',    'From Email Address',   'email'),
('smtp_from_name',  '',     'text',    'From Name',            'email'),
('smtp_encryption', 'tls',  'text',    'Encryption',           'email');

-- Fix group_name for any smtp settings that got wrong group
UPDATE settings SET group_name='email' WHERE `key` IN (
  'smtp_enabled','smtp_host','smtp_port','smtp_user','smtp_pass',
  'smtp_from_email','smtp_from_name','smtp_encryption'
) AND (group_name IS NULL OR group_name != 'email');

-- If smtp_from_email is empty, copy from smtp_user
UPDATE settings s1
JOIN settings s2 ON s2.`key` = 'smtp_user'
SET s1.value = s2.value
WHERE s1.`key` = 'smtp_from_email' AND (s1.value IS NULL OR s1.value = '');
