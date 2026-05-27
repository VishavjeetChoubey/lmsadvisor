<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

class GamificationService
{
    /** Check and award all applicable badges to a user. Call after key events. */
    public static function checkAndAward(int $userId): array
    {
        if (!(bool)(int)Setting::get('gamification_enabled', '1')) return [];

        $pdo     = Database::getInstance();
        $awarded = [];

        // Get all active badges not yet earned by this user
        $badges = $pdo->prepare(
            'SELECT b.* FROM badges b
             WHERE b.is_active=1
               AND b.id NOT IN (SELECT badge_id FROM user_badges WHERE user_id=?)
             ORDER BY b.rule_value ASC'
        );
        $badges->execute([$userId]);

        foreach ($badges->fetchAll() as $badge) {
            if (self::meetsRule($userId, $badge)) {
                $pdo->prepare(
                    'INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?,?)'
                )->execute([$userId, $badge['id']]);
                $awarded[] = $badge;

                // Fire in-app notification
                NotificationService::send(
                    $userId,
                    '🏅 Badge Earned: ' . $badge['name'],
                    $badge['description'] ?? 'You earned a new badge!'
                );
            }
        }
        return $awarded;
    }

    private static function meetsRule(int $userId, array $badge): bool
    {
        $pdo = Database::getInstance();
        return match($badge['rule_type']) {
            'courses_completed' => (function() use ($pdo, $userId, $badge) {
                $r = $pdo->prepare(
                    'SELECT COUNT(*) FROM enrollments WHERE user_id=? AND status=\'completed\''
                );
                $r->execute([$userId]);
                return (int)$r->fetchColumn() >= (int)$badge['rule_value'];
            })(),
            'quiz_score' => (function() use ($pdo, $userId, $badge) {
                $r = $pdo->prepare(
                    'SELECT MAX(score) FROM quiz_attempts WHERE user_id=? AND passed=1'
                );
                $r->execute([$userId]);
                return (int)$r->fetchColumn() >= (int)$badge['rule_value'];
            })(),
            'login_streak' => (function() use ($pdo, $userId, $badge) {
                $r = $pdo->prepare(
                    'SELECT current_days FROM login_streaks WHERE user_id=?'
                );
                $r->execute([$userId]);
                return (int)$r->fetchColumn() >= (int)$badge['rule_value'];
            })(),
            'grade_points' => (function() use ($pdo, $userId, $badge) {
                $r = $pdo->prepare(
                    'SELECT COALESCE(SUM(points),0) FROM grade_points WHERE user_id=?'
                );
                $r->execute([$userId]);
                return (int)$r->fetchColumn() >= (int)$badge['rule_value'];
            })(),
            default => false,
        };
    }

    /** Update login streak. Call on every successful login. */
    public static function updateStreak(int $userId): int
    {
        $pdo   = Database::getInstance();
        $today = date('Y-m-d');

        $row = $pdo->prepare('SELECT * FROM login_streaks WHERE user_id=? LIMIT 1');
        $row->execute([$userId]);
        $streak = $row->fetch();

        if (!$streak) {
            $pdo->prepare(
                'INSERT INTO login_streaks (user_id, current_days, longest_days, last_login)
                 VALUES (?,1,1,?)'
            )->execute([$userId, $today]);
            return 1;
        }

        $last    = $streak['last_login'];
        $current = (int)$streak['current_days'];
        $longest = (int)$streak['longest_days'];

        if ($last === $today) {
            return $current; // already counted today
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($last === $yesterday) {
            $current++; // consecutive day
        } else {
            $current = 1; // streak broken
        }
        $longest = max($longest, $current);

        $pdo->prepare(
            'UPDATE login_streaks
             SET current_days=?, longest_days=?, last_login=? WHERE user_id=?'
        )->execute([$current, $longest, $today, $userId]);

        return $current;
    }

    /** Get user level based on grade points. */
    public static function getLevel(int $points): array
    {
        $levels = [
            ['name' => 'Platinum', 'min' => (int)Setting::get('level_platinum_pts', 5000), 'color' => '#7c3aed', 'icon' => 'bi-gem'],
            ['name' => 'Gold',     'min' => (int)Setting::get('level_gold_pts',    1500), 'color' => '#d97706', 'icon' => 'bi-trophy-fill'],
            ['name' => 'Silver',   'min' => (int)Setting::get('level_silver_pts',   500), 'color' => '#6b7280', 'icon' => 'bi-shield-fill'],
            ['name' => 'Bronze',   'min' => (int)Setting::get('level_bronze_pts',   100), 'color' => '#b45309', 'icon' => 'bi-circle-fill'],
            ['name' => 'Starter',  'min' => 0,                                            'color' => '#9ca3af', 'icon' => 'bi-person-fill'],
        ];
        foreach ($levels as $level) {
            if ($points >= $level['min']) return $level;
        }
        return $levels[4];
    }

    /** Get user stats for profile/leaderboard. */
    public static function userStats(int $userId): array
    {
        $pdo = Database::getInstance();

        $points = (int)$pdo->prepare(
            'SELECT COALESCE(SUM(points),0) FROM grade_points WHERE user_id=?'
        )->execute([$userId]) ? $pdo->prepare(
            'SELECT COALESCE(SUM(points),0) FROM grade_points WHERE user_id=?'
        ) : null;

        // Re-run properly
        $s = $pdo->prepare('SELECT COALESCE(SUM(points),0) FROM grade_points WHERE user_id=?');
        $s->execute([$userId]); $totalPoints = (int)$s->fetchColumn();

        $s = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE user_id=? AND status=\'completed\'');
        $s->execute([$userId]); $completed = (int)$s->fetchColumn();

        $s = $pdo->prepare('SELECT * FROM login_streaks WHERE user_id=?');
        $s->execute([$userId]); $streak = $s->fetch() ?: ['current_days'=>0,'longest_days'=>0];

        $s = $pdo->prepare('SELECT b.* FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.awarded_at DESC');
        $s->execute([$userId]); $badges = $s->fetchAll();

        return [
            'points'        => $totalPoints,
            'level'         => self::getLevel($totalPoints),
            'completed'     => $completed,
            'streak'        => (int)$streak['current_days'],
            'longest_streak'=> (int)$streak['longest_days'],
            'badges'        => $badges,
            'badge_count'   => count($badges),
        ];
    }
}
