-- Migration 0027: Role-based menu visibility settings

CREATE TABLE IF NOT EXISTS menu_permissions (
    id         INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    menu_key   VARCHAR(80)     NOT NULL UNIQUE,  -- e.g. 'courses', 'users'
    label      VARCHAR(80)     NOT NULL,
    roles      JSON            NOT NULL,          -- ["super_admin","admin","manager"]
    sort_order SMALLINT        DEFAULT 0,
    updated_at TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default permissions: super_admin sees everything
INSERT INTO menu_permissions (menu_key, label, roles, sort_order) VALUES
('dashboard',      'Dashboard',      '["super_admin","admin","manager","instructor"]', 1),
('analytics',      'Analytics',      '["super_admin","admin"]',                        2),
('learner_data',   'Learner Data',   '["super_admin","admin"]',                        3),
('at_risk',        'At-Risk',        '["super_admin","admin"]',                        4),
('courses',        'Courses',        '["super_admin","admin","manager","instructor"]', 5),
('learning_paths', 'Learning Paths', '["super_admin","admin","manager"]',              6),
('groups',         'Groups',         '["super_admin","admin","manager"]',              7),
('assignments',    'Assignments',    '["super_admin","admin","manager","instructor"]', 8),
('badges',         'Badges',         '["super_admin","admin"]',                        9),
('email',          'Email',          '["super_admin","admin"]',                       10),
('enrollments',    'Enrollments',    '["super_admin","admin","manager"]',             11),
('users',          'Users',          '["super_admin","admin"]',                       12),
('categories',     'Categories',     '["super_admin","admin"]',                       13),
('quizzes',        'Quizzes',        '["super_admin","admin","manager","instructor"]',14),
('forum',          'Forum',          '["super_admin","admin","manager"]',             15),
('reviews',        'Reviews',        '["super_admin","admin"]',                       16),
('leaderboard',    'Leaderboard',    '["super_admin","admin","manager"]',             17),
('knowledge_base', 'Knowledge Base', '["super_admin","admin","manager"]',             18),
('webinars',       'Webinars',       '["super_admin","admin","manager","instructor"]',19),
('reports',        'Reports',        '["super_admin","admin"]',                       20),
('api',            'API Tokens',     '["super_admin","admin"]',                       21),
('webhooks',       'Webhooks',       '["super_admin"]',                               22),
('settings',       'Settings',       '["super_admin","admin"]',                       23),
('database',       'Database',       '["super_admin"]',                               24),
('tenants',        'Tenants',        '["super_admin"]',                               25),
('corporate',      'Corporate',      '["super_admin","admin"]',                       26),
('marketplace',    'Marketplace',    '["super_admin","admin"]',                       27),
('reporting',      'Reporting',      '["super_admin","admin"]',                       28),
('help_center',    'Help Center',    '["super_admin","admin","manager","instructor"]',29)
ON DUPLICATE KEY UPDATE label=VALUES(label), sort_order=VALUES(sort_order);

-- Menu Settings item (super_admin only)
INSERT INTO menu_permissions (menu_key, label, roles, sort_order) VALUES
('menu_settings', 'Menu Permissions', '["super_admin"]', 30)
ON DUPLICATE KEY UPDATE label=VALUES(label), sort_order=VALUES(sort_order);
