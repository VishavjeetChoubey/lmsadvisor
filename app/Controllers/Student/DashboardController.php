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

    // ── GET /learn/courses/:uuid/learn ────────────────────────────────────────
    public function learn(array $params): void
    {
        $this->guard();
        $user     = AuthService::user();
        $courseModel = new \App\Models\Course();
        $course   = $courseModel->findByUuidFull($params['uuid'] ?? '');

        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->redirect('/learn/courses');
        }

        // Verify enrollment
        $enrollModel  = new Enrollment();
        $enrollment   = $enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);
        if (!$enrollment) {
            $this->flash('error', 'You are not enrolled in this course.');
            $this->redirect('/learn/courses');
        }

        // Load sections + lessons
        $sections = $courseModel->sectionsWithLessons((int)$course['id']);

        // Flatten all lessons for prev/next navigation
        $allLessons = [];
        foreach ($sections as $sec) {
            foreach ($sec['lessons'] as $les) {
                $allLessons[] = $les;
            }
        }

        // Determine current lesson
        $lessonId      = (int)$this->request->get('lesson', 0);
        $currentLesson = null;
        $currentIndex  = 0;

        if ($lessonId) {
            foreach ($allLessons as $i => $les) {
                if ((int)$les['id'] === $lessonId) {
                    $currentLesson = $les;
                    $currentIndex  = $i;
                    break;
                }
            }
        }

        // Default to first lesson
        if (!$currentLesson && !empty($allLessons)) {
            $currentLesson = $allLessons[0];
            $currentIndex  = 0;
        }

        $prevLesson = $allLessons[$currentIndex - 1] ?? null;
        $nextLesson = $allLessons[$currentIndex + 1] ?? null;

        // Load lesson progress for this enrollment
        $pdo  = \App\Core\Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT lesson_id, status, progress_pct FROM lesson_progress
             WHERE enrollment_id = ?'
        );
        $stmt->execute([$enrollment['id']]);
        $lessonProgress = [];
        foreach ($stmt->fetchAll() as $row) {
            $lessonProgress[$row['lesson_id']] = $row;
        }

        // Calculate course progress
        $totalLessons    = count($allLessons);
        $completedLessons = count(array_filter($lessonProgress, fn($lp) => $lp['status'] === 'completed'));
        $courseProgress   = $totalLessons > 0 ? round($completedLessons / $totalLessons * 100) : 0;

        $this->view('student.lesson.player', [
            'title'          => ($currentLesson['title'] ?? 'Course') . ' — LMSAdvisor',
            'page_title'     => $course['title'],
            'auth_user'      => $user,
            'flash'          => $this->getFlash(),
            'course'         => $course,
            'sections'       => $sections,
            'currentLesson'  => $currentLesson,
            'prevLesson'     => $prevLesson,
            'nextLesson'     => $nextLesson,
            'lessonProgress' => $lessonProgress,
            'courseProgress' => $courseProgress,
            'enrollment'     => $enrollment,
            'csrf_token'     => \App\Middleware\CsrfMiddleware::token(),
        ], 'student');
    }

    // ── POST /learn/courses/:uuid/complete-lesson ─────────────────────────────
    public function completeLesson(array $params): void
    {
        $this->guard();
        \App\Middleware\CsrfMiddleware::verify();

        $user        = AuthService::user();
        $courseModel = new \App\Models\Course();
        $course      = $courseModel->findByUuidFull($params['uuid'] ?? '');

        if (!$course) {
            $this->redirect('/learn/courses');
        }

        $enrollModel = new Enrollment();
        $enrollment  = $enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);

        if (!$enrollment) {
            $this->redirect('/learn/courses');
        }

        $lessonId = (int)$this->request->post('lesson_id', 0);
        if ($lessonId) {
            try {
                $enrollModel->markLessonProgress((int)$enrollment['id'], $lessonId, 'completed', 100);
            } catch (\Throwable $e) {
                error_log('[completeLesson] markLessonProgress failed: ' . $e->getMessage());
                $this->flash('error', 'Could not save progress. Please try again.');
                $this->redirect('/learn/courses/' . ($course['uuid'] ?? '') . '/learn?lesson=' . $lessonId);
            }
        }

        // Check if all lessons are complete → mark enrollment complete
        $pdo  = \App\Core\Database::getInstance();
        $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ?');
        $totalStmt->execute([$course['id']]);
        $total = (int)$totalStmt->fetchColumn();

        $doneStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM lesson_progress WHERE enrollment_id = ? AND status = "completed"'
        );
        $doneStmt->execute([$enrollment['id']]);
        $done = (int)$doneStmt->fetchColumn();

        if ($total > 0 && $done >= $total) {
            $enrollModel->updateStatus((int)$enrollment['id'], 'completed');
            $this->flash('success', '🎉 Congratulations! You have completed this course.');
        }

        // Find next lesson
        $sections   = $courseModel->sectionsWithLessons((int)$course['id']);
        $allLessons = [];
        foreach ($sections as $sec) {
            foreach ($sec['lessons'] as $les) {
                $allLessons[] = $les;
            }
        }
        $nextId = null;
        foreach ($allLessons as $i => $les) {
            if ((int)$les['id'] === $lessonId && isset($allLessons[$i + 1])) {
                $nextId = $allLessons[$i + 1]['id'];
                break;
            }
        }

        $redirect = '/learn/courses/' . $course['uuid'] . '/learn?lesson=' . ($nextId ?? $lessonId);
        $this->redirect($redirect);
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
