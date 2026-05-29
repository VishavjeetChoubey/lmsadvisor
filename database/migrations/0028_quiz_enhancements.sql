-- Migration 0028: New question types + quiz gating

-- Add new question types
ALTER TABLE questions MODIFY COLUMN type
  ENUM('single','multiple','true_false','fill_blank','ordering','short_answer','matching')
  DEFAULT 'single';

-- Matching pairs (stored as JSON: [{"left":"term","right":"definition"}])
ALTER TABLE questions ADD COLUMN IF NOT EXISTS match_pairs JSON NULL AFTER explanation;

-- Ordering items (stored as JSON: ["step1","step2","step3"])
ALTER TABLE questions ADD COLUMN IF NOT EXISTS order_items JSON NULL AFTER match_pairs;

-- Short answer: acceptable answers as JSON array
ALTER TABLE questions ADD COLUMN IF NOT EXISTS acceptable_answers JSON NULL AFTER order_items;

-- Quiz gating: block lesson completion if quiz not passed
ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS is_required     TINYINT(1) DEFAULT 0 AFTER adaptive_start_difficulty;
ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS blocks_progress TINYINT(1) DEFAULT 1 AFTER is_required;
