-- Migration 0012: Custom Code settings keys
INSERT IGNORE INTO settings (`key`, value, type, label, group_name) VALUES
('custom_css',       '', 'textarea', 'Custom CSS',        'custom_code'),
('custom_js_head',   '', 'textarea', 'Head JavaScript',   'custom_code'),
('custom_js_body',   '', 'textarea', 'Body JavaScript',   'custom_code'),
('custom_js_footer', '', 'textarea', 'Footer JavaScript', 'custom_code');
