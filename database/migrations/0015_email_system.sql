-- Migration 0015: Email notification system

CREATE TABLE IF NOT EXISTS email_queue (
  id           INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  user_id      INT UNSIGNED,
  to_email     VARCHAR(255)  NOT NULL,
  to_name      VARCHAR(120),
  subject      VARCHAR(255)  NOT NULL,
  body_html    LONGTEXT      NOT NULL,
  template     VARCHAR(60),
  status       ENUM('pending','sent','failed') DEFAULT 'pending',
  attempts     TINYINT UNSIGNED DEFAULT 0,
  error_msg    VARCHAR(500),
  scheduled_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  sent_at      TIMESTAMP     NULL,
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_eq_status (status),
  INDEX idx_eq_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_templates (
  id         INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  slug       VARCHAR(60)   NOT NULL UNIQUE,
  name       VARCHAR(120)  NOT NULL,
  subject    VARCHAR(255)  NOT NULL,
  body_html  LONGTEXT      NOT NULL,
  variables  VARCHAR(500),
  is_enabled TINYINT(1)    DEFAULT 1,
  updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_unsubscribes (
  id         INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  email      VARCHAR(255)  NOT NULL UNIQUE,
  token      CHAR(64)      NOT NULL UNIQUE,
  created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default email templates (plain HTML placeholders — edit in Admin > Email)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'enrollment_confirmation',
  'Enrollment Confirmation',
  'You are enrolled in: {{course_title}}',
  '<p>Hi {{student_name}}, you are enrolled in <strong>{{course_title}}</strong>. <a href="{{course_url}}">Start Learning</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","course_title","course_level","course_duration","grade_points","course_url","site_name","site_logo","unsubscribe_url"]'
);

INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'course_completion',
  'Course Completion',
  'Congratulations! You completed: {{course_title}}',
  '<p>Hi {{student_name}}, congratulations on completing <strong>{{course_title}}</strong>! You earned <strong>{{grade_points}} grade points</strong>. <a href="{{certificate_url}}">Download Certificate</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","course_title","grade_points","certificate_url","course_url","site_name","unsubscribe_url"]'
);

INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'quiz_result',
  'Quiz Result',
  'Quiz Result: {{quiz_title}} - {{result}}',
  '<p>Hi {{student_name}}, your result for <strong>{{quiz_title}}</strong> in {{course_title}}: <strong>{{score}}%</strong> (pass mark: {{pass_percentage}}%). Result: {{result}}</p><p><a href="{{course_url}}">Continue Learning</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","quiz_title","course_title","score","pass_percentage","result","result_emoji","result_color","course_url","site_name","unsubscribe_url"]'
);

INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'webinar_reminder',
  'Webinar Reminder',
  'Reminder: {{webinar_title}} starts in 24 hours',
  '<p>Hi {{student_name}}, your webinar <strong>{{webinar_title}}</strong> starts in 24 hours. Date: {{webinar_date}} at {{webinar_time}} ({{webinar_duration}} min) via {{webinar_provider}}.</p><p><a href="{{join_url}}">Join Webinar</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","webinar_title","webinar_date","webinar_time","webinar_duration","webinar_provider","join_url","site_name","unsubscribe_url"]'
);

INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (
  'certificate_ready',
  'Certificate Ready',
  'Your certificate for {{course_title}} is ready!',
  '<p>Hi {{student_name}}, your certificate for <strong>{{course_title}}</strong> is ready. <a href="{{certificate_url}}">View Certificate</a></p><p style="font-size:12px"><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
  '["student_name","course_title","certificate_url","site_name","unsubscribe_url"]'
);
