-- Migration 0013: Move leaderboard settings to 'reviews' group
-- They display on the same Settings tab as Reviews, so must share the group name
UPDATE settings SET group_name = 'reviews' 
WHERE `key` IN ('leaderboard_enabled', 'leaderboard_public')
  AND group_name = 'leaderboard';
