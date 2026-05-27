-- Migration 0015: Email notification system

CREATE TABLE IF NOT EXISTS email_queue (
  id           INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  user_id      INT UNSIGNED,
  to_email     VARCHAR(255)  NOT NULL,
  to_name      VARCHAR(120),
  subject      VARCHAR(255)  NOT NULL,
  body_html    LONGTEXT      NOT NULL,
  template     VARCHAR(60)   COMMENT 'enrollment,completion,quiz_result,webinar,certificate',
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
  slug       VARCHAR(60)   NOT NULL UNIQUE COMMENT 'enrollment_confirmation, course_completion, etc.',
  name       VARCHAR(120)  NOT NULL,
  subject    VARCHAR(255)  NOT NULL,
  body_html  LONGTEXT      NOT NULL,
  variables  VARCHAR(500)  COMMENT 'JSON list of available variables',
  is_enabled TINYINT(1)    DEFAULT 1,
  updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_unsubscribes (
  id         INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  email      VARCHAR(255)  NOT NULL UNIQUE,
  token      CHAR(64)      NOT NULL UNIQUE,
  created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default email templates
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES
('enrollment_confirmation', 'Enrollment Confirmation',
 'You are enrolled in: {{course_title}}',
 '<!DOCTYPE html><html><body style="font-family:Inter,sans-serif;background:#f5f6fa;padding:40px 0"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)"><div style="background:linear-gradient(135deg,#5b5ef6,#3b82f6);padding:32px 36px"><img src="{{site_logo}}" style="height:36px" alt="{{site_name}}"><h1 style="color:#fff;margin:20px 0 0;font-size:24px">Welcome to your course!</h1></div><div style="padding:32px 36px"><p style="font-size:16px;color:#374151">Hi {{student_name}},</p><p style="font-size:15px;color:#6b7280;line-height:1.7">You have been successfully enrolled in <strong style="color:#111827">{{course_title}}</strong>.</p><div style="background:#f5f5ff;border-radius:12px;padding:20px;margin:24px 0"><p style="margin:0 0 8px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Course Details</p><p style="margin:4px 0;font-size:14px;color:#374151">📚 Level: {{course_level}}</p><p style="margin:4px 0;font-size:14px;color:#374151">⏱ Duration: {{course_duration}}</p><p style="margin:4px 0;font-size:14px;color:#374151">🏆 Points: {{grade_points}} on completion</p></div><a href="{{course_url}}" style="display:inline-block;background:linear-gradient(135deg,#5b5ef6,#3b82f6);color:#fff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:700;font-size:15px">Start Learning →</a><hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0"><p style="font-size:12px;color:#9ca3af">You received this email because you enrolled at {{site_name}}. <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p></div></div></body></html>',
 '["student_name","course_title","course_level","course_duration","grade_points","course_url","site_name","site_logo","unsubscribe_url"]'),

('course_completion', 'Course Completion',
 'Congratulations! You completed: {{course_title}} 🎉',
 '<!DOCTYPE html><html><body style="font-family:Inter,sans-serif;background:#f5f6fa;padding:40px 0"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)"><div style="background:linear-gradient(135deg,#059669,#10b981);padding:32px 36px;text-align:center"><div style="font-size:56px">🎉</div><h1 style="color:#fff;margin:12px 0 0;font-size:26px">Course Completed!</h1></div><div style="padding:32px 36px"><p style="font-size:16px;color:#374151">Hi {{student_name}},</p><p style="font-size:15px;color:#6b7280;line-height:1.7">Congratulations on completing <strong style="color:#111827">{{course_title}}</strong>! You earned <strong style="color:#059669">{{grade_points}} grade points</strong>.</p><p style="font-size:15px;color:#6b7280">Your certificate of completion is ready to download.</p><div style="text-align:center;margin:28px 0"><a href="{{certificate_url}}" style="display:inline-block;background:linear-gradient(135deg,#059669,#10b981);color:#fff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:700;font-size:15px;margin-right:12px">Download Certificate</a><a href="{{course_url}}" style="display:inline-block;background:#f3f4f6;color:#374151;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:700;font-size:15px">Review Course</a></div><hr style="border:none;border-top:1px solid #e5e7eb;margin:28px 0"><p style="font-size:12px;color:#9ca3af">{{site_name}} · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p></div></div></body></html>',
 '["student_name","course_title","grade_points","certificate_url","course_url","site_name","unsubscribe_url"]'),

('quiz_result', 'Quiz Result',
 'Quiz Result: {{quiz_title}} — {{result}}',
 '<!DOCTYPE html><html><body style="font-family:Inter,sans-serif;background:#f5f6fa;padding:40px 0"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)"><div style="background:{{result_color}};padding:32px 36px;text-align:center"><div style="font-size:48px">{{result_emoji}}</div><h1 style="color:#fff;margin:12px 0 0;font-size:24px">{{result}}</h1></div><div style="padding:32px 36px"><p style="font-size:16px;color:#374151">Hi {{student_name}},</p><p style="font-size:15px;color:#6b7280;line-height:1.7">Your quiz result for <strong>{{quiz_title}}</strong> in <strong>{{course_title}}</strong>:</p><div style="background:#f9fafb;border-radius:12px;padding:24px;text-align:center;margin:20px 0"><div style="font-size:48px;font-weight:800;color:{{result_color}}">{{score}}%</div><div style="font-size:14px;color:#6b7280;margin-top:4px">Pass mark: {{pass_percentage}}%</div></div><a href="{{course_url}}" style="display:inline-block;background:#5b5ef6;color:#fff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:700;font-size:15px">Continue Learning</a><hr style="border:none;border-top:1px solid #e5e7eb;margin:28px 0"><p style="font-size:12px;color:#9ca3af">{{site_name}} · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p></div></div></body></html>',
 '["student_name","quiz_title","course_title","score","pass_percentage","result","result_emoji","result_color","course_url","site_name","unsubscribe_url"]'),

('webinar_reminder', 'Webinar Reminder',
 'Reminder: {{webinar_title}} starts in 24 hours',
 '<!DOCTYPE html><html><body style="font-family:Inter,sans-serif;background:#f5f6fa;padding:40px 0"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)"><div style="background:linear-gradient(135deg,#7c3aed,#5b5ef6);padding:32px 36px"><div style="font-size:36px">📹</div><h1 style="color:#fff;margin:12px 0 0;font-size:24px">Webinar Starting Soon</h1></div><div style="padding:32px 36px"><p style="font-size:16px;color:#374151">Hi {{student_name}},</p><p style="font-size:15px;color:#6b7280;line-height:1.7">Your webinar <strong style="color:#111827">{{webinar_title}}</strong> starts in <strong>24 hours</strong>.</p><div style="background:#f5f5ff;border-radius:12px;padding:20px;margin:20px 0"><p style="margin:4px 0;font-size:14px;color:#374151">📅 Date: {{webinar_date}}</p><p style="margin:4px 0;font-size:14px;color:#374151">⏰ Time: {{webinar_time}}</p><p style="margin:4px 0;font-size:14px;color:#374151">⏱ Duration: {{webinar_duration}} minutes</p><p style="margin:4px 0;font-size:14px;color:#374151">📹 Platform: {{webinar_provider}}</p></div><a href="{{join_url}}" style="display:inline-block;background:linear-gradient(135deg,#7c3aed,#5b5ef6);color:#fff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:700;font-size:15px">Join Webinar →</a><hr style="border:none;border-top:1px solid #e5e7eb;margin:28px 0"><p style="font-size:12px;color:#9ca3af">{{site_name}} · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p></div></div></body></html>',
 '["student_name","webinar_title","webinar_date","webinar_time","webinar_duration","webinar_provider","join_url","site_name","unsubscribe_url"]'),

('certificate_ready', 'Certificate Ready',
 'Your certificate for {{course_title}} is ready!',
 '<!DOCTYPE html><html><body style="font-family:Inter,sans-serif;background:#f5f6fa;padding:40px 0"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)"><div style="background:linear-gradient(135deg,#d97706,#f59e0b);padding:32px 36px;text-align:center"><div style="font-size:48px">🏆</div><h1 style="color:#fff;margin:12px 0 0;font-size:24px">Certificate Ready!</h1></div><div style="padding:32px 36px"><p style="font-size:16px;color:#374151">Hi {{student_name}},</p><p style="font-size:15px;color:#6b7280;line-height:1.7">Your certificate of completion for <strong style="color:#111827">{{course_title}}</strong> is ready. Share it on LinkedIn or download it for your records.</p><div style="text-align:center;margin:28px 0"><a href="{{certificate_url}}" style="display:inline-block;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px">View Certificate →</a></div><hr style="border:none;border-top:1px solid #e5e7eb;margin:28px 0"><p style="font-size:12px;color:#9ca3af">{{site_name}} · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p></div></div></body></html>',
 '["student_name","course_title","certificate_url","site_name","unsubscribe_url"]');
