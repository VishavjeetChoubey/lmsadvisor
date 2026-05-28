-- Migration 0024: Adaptive quizzes + recommendations + drop-out predictor

-- Add difficulty to questions (1=easy, 2=medium, 3=hard)
ALTER TABLE questions ADD COLUMN IF NOT EXISTS difficulty TINYINT UNSIGNED DEFAULT 2 AFTER sort_order;
ALTER TABLE questions ADD COLUMN IF NOT EXISTS times_shown INT UNSIGNED DEFAULT 0 AFTER difficulty;
ALTER TABLE questions ADD COLUMN IF NOT EXISTS times_correct INT UNSIGNED DEFAULT 0 AFTER times_shown;

-- Track per-question performance per student
CREATE TABLE IF NOT EXISTS question_responses (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    attempt_id  INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    is_correct  TINYINT(1)   NOT NULL DEFAULT 0,
    time_sec    SMALLINT UNSIGNED DEFAULT 0,
    answered_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_qr_user    (user_id),
    KEY idx_qr_question(question_id),
    FOREIGN KEY (attempt_id)  REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id)     ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enable adaptive mode per quiz
ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS adaptive_mode TINYINT(1) DEFAULT 0 AFTER max_attempts;
ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS adaptive_start_difficulty TINYINT UNSIGNED DEFAULT 2 AFTER adaptive_mode;

-- Course recommendations
CREATE TABLE IF NOT EXISTS course_recommendations (
    id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id      INT UNSIGNED NOT NULL,
    course_id    INT UNSIGNED NOT NULL,
    score        DECIMAL(5,2) NOT NULL DEFAULT 0,
    reason       VARCHAR(200),
    generated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dismissed    TINYINT(1)   DEFAULT 0,
    UNIQUE KEY uq_rec (user_id, course_id),
    KEY idx_rec_user  (user_id),
    KEY idx_rec_score (score),
    FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drop-out risk scores
CREATE TABLE IF NOT EXISTS dropout_risk (
    id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id      INT UNSIGNED NOT NULL,
    enrollment_id INT UNSIGNED NOT NULL,
    risk_score   DECIMAL(5,2) NOT NULL DEFAULT 0,
    risk_level   ENUM('low','medium','high','critical') DEFAULT 'low',
    factors      JSON,
    alert_sent   TINYINT(1)   DEFAULT 0,
    calculated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_dropout (enrollment_id),
    KEY idx_dropout_risk  (risk_level),
    KEY idx_dropout_user  (user_id),
    FOREIGN KEY (user_id)      REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
