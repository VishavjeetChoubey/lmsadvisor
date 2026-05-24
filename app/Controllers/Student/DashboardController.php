<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\LeaderboardService;
use App\Models\Enrollment;

class DashboardController extends Controller
{
    private function guard(): void
    {
        AuthMiddleware::handle('/login');
    }

    // ── GET /learn  /learn/dashboard ─────────────────────────────────────────
    public function index(array $params): void
    {
        $this->guard();
        $user      = AuthService::user();
        $model     = new Enrollment();
        $enrolled  = $model->forUser((int)$user['id']);
        $points    = LeaderboardService::totalPoints((int)$user['id']);

        $completed  = count(array_filter($enrolled, fn($e) => $e['status'] === 'completed'));
        $active     = count(array_filter($enrolled, fn($e) => $e['status'] === 'active'));

        $this->view('student.dashboard.index', [
            'title'      => 'My Dashboard — LMSAdvisor',
            'page_title' => 'My Dashboard',
            'auth_user'  => $user,
            'flash'      => $this->getFlash(),
            'enrolled'   => $enrolled,
            'points'     => $points,
            'completed'  => $completed,
            'active'     => $active,
        ], 'student');
    }

    // ── GET /learn/courses ────────────────────────────────────────────────────
    public function courses(array $params): void
    {
        $this->guard();
        $user    = AuthService::user();
        $model   = new Enrollment();
        $enrolled = $model->forUser((int)$user['id']);

        $this->view('student.courses.index', [
            'title'      => 'My Courses — LMSAdvisor',
            'page_title' => 'My Courses',
            'auth_user'  => $user,
            'flash'      => $this->getFlash(),
            'enrolled'   => $enrolled,
        ], 'student');
    }

    // ── GET /learn/calendar ───────────────────────────────────────────────────
    public function calendar(array $params): void
    {
        $this->guard();
        $user   = AuthService::user();
        $events = \App\Services\CalendarService::forUser((int)$user['id']);
        $fcEvents = \App\Services\CalendarService::toFullCalendarEvents($events);

        $this->view('student.calendar.index', [
            'title'      => 'My Calendar — LMSAdvisor',
            'page_title' => 'My Calendar',
            'auth_user'  => $user,
            'flash'      => $this->getFlash(),
            'events'     => $fcEvents,
        ], 'student');
    }

    // ── GET /learn/leaderboard ────────────────────────────────────────────────
    public function leaderboard(array $params): void
    {
        $this->guard();
        $user   = AuthService::user();
        $top    = \App\Services\LeaderboardService::topN(50);
        $myPts  = LeaderboardService::totalPoints((int)$user['id']);

        // Find my rank
        $myRank = 0;
        foreach ($top as $i => $u) {
            if ((int)$u['id'] === (int)$user['id']) {
                $myRank = $i + 1;
                break;
            }
        }

        $this->view('student.leaderboard.index', [
            'title'      => 'Leaderboard — LMSAdvisor',
            'page_title' => 'Leaderboard',
            'auth_user'  => $user,
            'flash'      => $this->getFlash(),
            'top'        => $top,
            'myPoints'   => $myPts,
            'myRank'     => $myRank,
        ], 'student');
    }

    // ── GET /learn/profile ────────────────────────────────────────────────────
    public function profile(array $params): void
    {
        $this->guard();
        $user      = AuthService::user();
        $model     = new Enrollment();
        $enrolled  = $model->forUser((int)$user['id']);
        $points    = LeaderboardService::totalPoints((int)$user['id']);

        $this->view('student.profile.index', [
            'title'      => 'My Profile — LMSAdvisor',
            'page_title' => 'My Profile',
            'auth_user'  => $user,
            'flash'      => $this->getFlash(),
            'enrolled'   => $enrolled,
            'points'     => $points,
        ], 'student');
    }
}
