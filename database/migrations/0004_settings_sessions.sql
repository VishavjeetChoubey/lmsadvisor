-- Migration 0004: Settings table + default seed data
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `key`      VARCHAR(120) NOT NULL UNIQUE,
  value      LONGTEXT,
  type       ENUM('text','textarea','color','image','boolean','json','password') DEFAULT 'text',
  label      VARCHAR(191),
  group_name VARCHAR(80)  DEFAULT 'general',
  updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (`key`, value, type, label, group_name) VALUES
-- General
('site_name',            'LMSAdvisor',  'text',    'Site Name',                'general'),
('site_tagline',         '',            'text',    'Site Tagline',             'general'),
('site_logo',            '',            'image',   'Site Logo',                'general'),
('site_favicon',         '',            'image',   'Favicon',                  'general'),
('theme_color',          '#1a56db',     'color',   'Primary Color',            'general'),
('admin_email',          '',            'text',    'Admin Email',              'general'),
('timezone',             'UTC',         'text',    'Timezone',                 'general'),
('date_format',          'D M Y',       'text',    'Date Format',              'general'),
-- Security
('recaptcha_enabled',    '0',           'boolean', 'Enable reCAPTCHA',         'security'),
('recaptcha_site_key',   '',            'text',    'reCAPTCHA Site Key',       'security'),
('recaptcha_secret',     '',            'password','reCAPTCHA Secret Key',     'security'),
('login_max_attempts',   '5',           'text',    'Max Login Attempts',       'security'),
('login_lockout_min',    '15',          'text',    'Lockout Duration (min)',   'security'),
-- Email
('smtp_enabled',         '0',           'boolean', 'Enable SMTP',              'email'),
('smtp_host',            '',            'text',    'SMTP Host',                'email'),
('smtp_port',            '587',         'text',    'SMTP Port',                'email'),
('smtp_user',            '',            'text',    'SMTP Username',            'email'),
('smtp_pass',            '',            'password','SMTP Password',            'email'),
('smtp_from_email',      '',            'text',    'From Email Address',       'email'),
('smtp_from',            '',            'text',    'From Address (legacy)',     'email'),
('smtp_from_name',       'LMS Advisor', 'text',    'From Name',                'email'),
('smtp_encryption',      'tls',         'text',    'Encryption (tls/ssl)',     'email'),
-- Certificates
('cert_signer_name',     '',            'text',    'Signatory Name',           'certificates'),
('cert_signer_title',    '',            'text',    'Signatory Title',          'certificates'),
('cert_footer_text',     '',            'textarea','Certificate Footer Text',  'certificates'),
('cert_template_color',  '#1a56db',     'color',   'Certificate Accent Color', 'certificates'),
-- Social Login
('social_google_enabled','0',           'boolean', 'Enable Google Login',      'social_login'),
('social_google_id',     '',            'text',    'Google Client ID',         'social_login'),
('social_google_secret', '',            'password','Google Client Secret',     'social_login'),
('social_github_enabled','0',           'boolean', 'Enable GitHub Login',      'social_login'),
('social_github_id',     '',            'text',    'GitHub Client ID',         'social_login'),
('social_github_secret', '',            'password','GitHub Client Secret',     'social_login'),
-- Webinar
('zoom_enabled',         '0',           'boolean', 'Enable Zoom Integration',  'webinar'),
('zoom_api_key',         '',            'text',    'Zoom API Key',             'webinar'),
('zoom_api_secret',      '',            'password','Zoom API Secret',          'webinar'),
('zoom_account_id',      '',            'text',    'Zoom Account ID',          'webinar'),
('gmeet_enabled',        '0',           'boolean', 'Enable Google Meet',       'webinar'),
('gmeet_oauth_json',     '',            'textarea','Google OAuth JSON',         'webinar'),
-- AI Integration
('ai_provider',          'openai',      'text',    'AI Provider (openai|anthropic)', 'ai'),
('ai_openai_key',        '',            'password','OpenAI API Key',           'ai'),
('ai_anthropic_key',     '',            'password','Anthropic (Claude) Key',   'ai'),
('ai_model',             'gpt-4o',      'text',    'Model Name',               'ai'),
('ai_enabled',           '0',           'boolean', 'Enable AI Features',       'ai'),
-- Reviews
('reviews_enabled',      '1',           'boolean', 'Enable Course Reviews',    'reviews'),
('reviews_auto_approve', '0',           'boolean', 'Auto-approve Reviews',     'reviews'),
-- Leaderboard
('leaderboard_enabled',  '1',           'boolean', 'Enable Leaderboard',       'leaderboard'),
('leaderboard_public',   '1',           'boolean', 'Public Leaderboard',       'leaderboard');

-- Lesson player feature toggles
INSERT IGNORE INTO settings (`key`, value, type, label, group_name) VALUES
('lesson_show_ai_tutor',   '1', 'boolean', 'Show AI Tutor on lesson pages',     'lesson'),
('lesson_show_notes',      '1', 'boolean', 'Show Notes & Comments panel',        'lesson'),
('lesson_show_collab_fab', '1', 'boolean', 'Show Notes floating button',         'lesson'),
('lesson_allow_dark_mode', '1', 'boolean', 'Allow students to toggle dark mode', 'lesson');
