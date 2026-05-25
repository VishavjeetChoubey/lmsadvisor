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

    // ── GET /learn/courses/:uuid — Course detail page ─────────────────────────
    public function courseDetail(array $params): void
    {
        $this->guard();
        $user        = AuthService::user();
        $courseModel = new \App\Models\Course();
        $course      = $courseModel->findByUuidFull($params['uuid'] ?? '');

        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->redirect('/learn/courses');
        }

        $enrollModel = new Enrollment();
        $enrollment  = $enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);

        if (!$enrollment) {
            $this->flash('error', 'You are not enrolled in this course.');
            $this->redirect('/learn/courses');
        }

        // Load sections + lessons for outline
        $sections = $courseModel->sectionsWithLessons((int)$course['id']);

        // Count progress
        $pdo = \App\Core\Database::getInstance();
        $prog = $pdo->prepare(
            'SELECT lesson_id, status FROM lesson_progress WHERE enrollment_id=?'
        );
        $prog->execute([$enrollment['id']]);
        $lessonProgress = [];
        foreach ($prog->fetchAll() as $row) {
            $lessonProgress[$row['lesson_id']] = $row['status'];
        }

        // Reviews
        $reviews = $pdo->prepare(
            'SELECT cr.rating, cr.review AS comment, cr.created_at, u.first_name, u.last_name
             FROM course_reviews cr JOIN users u ON u.id=cr.user_id
             WHERE cr.course_id=? AND cr.is_approved=1
             ORDER BY cr.created_at DESC LIMIT 5'
        );
        $reviews->execute([$course['id']]);
        $reviewList = $reviews->fetchAll();

        $avgRating = $pdo->prepare(
            'SELECT ROUND(AVG(rating),1), COUNT(*) FROM course_reviews WHERE course_id=? AND is_approved=1'
        );
        $avgRating->execute([$course['id']]);
        [$avgRat, $reviewCount] = $avgRating->fetch(\PDO::FETCH_NUM);

        // Instructors
        $instrStmt = $pdo->prepare(
            'SELECT u.first_name, u.last_name, u.email, cm.role AS cm_role
             FROM course_managers cm JOIN users u ON u.id=cm.user_id
             WHERE cm.course_id=?'
        );
        $instrStmt->execute([$course['id']]);
        $instructors = $instrStmt->fetchAll();

        // Find first incomplete lesson for resume URL
        $allLessons = [];
        foreach ($sections as $sec) {
            foreach ($sec['lessons'] as $les) {
                $allLessons[] = $les;
            }
        }
        $resumeLessonId = null;
        foreach ($allLessons as $les) {
            if (($lessonProgress[$les['id']] ?? '') !== 'completed') {
                $resumeLessonId = $les['id'];
                break;
            }
        }
        // If all complete, go to last lesson
        if (!$resumeLessonId && !empty($allLessons)) {
            $resumeLessonId = end($allLessons)['id'];
        }

        $totalLessons    = count($allLessons);
        $completedCount  = count(array_filter($lessonProgress, fn($s) => $s === 'completed'));
        $progressPct     = $totalLessons > 0 ? round($completedCount / $totalLessons * 100) : 0;

        $this->view('student.courses.detail', [
            'title'          => $course['title'] . ' — LMSAdvisor',
            'page_title'     => $course['title'],
            'auth_user'      => $user,
            'flash'          => $this->getFlash(),
            'course'         => $course,
            'enrollment'     => $enrollment,
            'sections'       => $sections,
            'lessonProgress' => $lessonProgress,
            'reviewList'     => $reviewList,
            'avgRating'      => (float)($avgRat ?? 0),
            'reviewCount'    => (int)($reviewCount ?? 0),
            'instructors'    => $instructors,
            'resumeLessonId' => $resumeLessonId,
            'progressPct'    => $progressPct,
            'totalLessons'   => $totalLessons,
            'completedCount' => $completedCount,
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

        // Resume from FIRST INCOMPLETE lesson (not always lesson 0)
        if (!$currentLesson && !empty($allLessons)) {
            foreach ($allLessons as $i => $les) {
                $status = ($lessonProgress[$les['id']]['status'] ?? '');
                if ($status !== 'completed') {
                    $currentLesson = $les;
                    $currentIndex  = $i;
                    break;
                }
            }
            // If all complete — show last lesson
            if (!$currentLesson) {
                $currentLesson = end($allLessons);
                $currentIndex  = count($allLessons) - 1;
            }
        }

        // ── Drip enforcement ──────────────────────────────────────────────────
        // If the course has drip enabled, check if this lesson is unlocked
        $dripEnabled = (bool)(int)($course['drip_enabled'] ?? 0);
        if ($dripEnabled && $currentLesson && (int)($currentLesson['drip_days'] ?? 0) > 0) {
            $enrolledAt  = strtotime($enrollment['enrolled_at']);
            $unlockAt    = $enrolledAt + ((int)$currentLesson['drip_days'] * 86400);
            if (time() < $unlockAt) {
                // Lesson is locked — redirect to first available lesson
                $this->flash('error', 'This lesson unlocks on ' . date('d M Y', $unlockAt) . '. Keep progressing!');
                $this->redirect('/learn/courses/' . ($course['uuid'] ?? '') . '/learn');
            }
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

            // Fire completion notification + auto-issue certificate
            \App\Services\NotificationService::onCompletion((int)$user['id'], $course['title']);
            try { \App\Services\CertificateService::issue((int)$enrollment['id'], (int)$user['id'], (int)$course['id']); } catch (\Throwable) {}
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

    // ── POST /learn/courses/:uuid/review ─────────────────────────────────────
    public function submitReview(array $params): void
    {
        $this->guard();
        \App\Middleware\CsrfMiddleware::verify();

        $user        = AuthService::user();
        $courseModel = new \App\Models\Course();
        $course      = $courseModel->findByUuidFull($params['uuid'] ?? '');
        if (!$course) { $this->json(['success'=>false,'message'=>'Course not found.']); }

        $enrollModel = new Enrollment();
        $enrollment  = $enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);
        if (!$enrollment) { $this->json(['success'=>false,'message'=>'You are not enrolled.']); }
        if ($enrollment['status'] !== 'completed') {
            $this->json(['success'=>false,'message'=>'Complete the course before leaving a review.']);
        }

        $rating  = min(5, max(1, (int)$this->request->post('rating', 5)));
        $comment = \App\Helpers\Sanitizer::string($this->request->post('review', ''), 1000);

        $pdo = \App\Core\Database::getInstance();

        // Check for existing review
        $existing = $pdo->prepare('SELECT id FROM course_reviews WHERE course_id=? AND user_id=? LIMIT 1');
        $existing->execute([$course['id'], $user['id']]);
        if ($existing->fetch()) {
            $this->json(['success'=>false,'message'=>'You have already reviewed this course.']);
        }

        $autoApprove = (bool)(int)\App\Models\Setting::get('reviews_auto_approve', 0);

        $pdo->prepare(
            'INSERT INTO course_reviews (course_id, user_id, rating, review, is_approved)
             VALUES (?,?,?,?,?)'
        )->execute([$course['id'], $user['id'], $rating, $comment, $autoApprove ? 1 : 0]);

        \App\Models\AuditLog::write('review.submit', 'course', (int)$course['id'], null, ['rating'=>$rating]);
        $this->json(['success'=>true,'message'=>$autoApprove ? 'Review published!' : 'Review submitted and awaiting approval. Thank you!']);
    }

    // ── GET /learn/profile ────────────────────────────────────────────────────
    public function profile(array $params): void
    {
        $this->guard();
        $user     = AuthService::user();
        $model    = new Enrollment();
        $enrolled = $model->forUser((int)$user['id']);
        $points   = LeaderboardService::totalPoints((int)$user['id']);

        // Load full user row for form fields
        $userModel = new \App\Models\User();
        $fullUser  = $userModel->findWithRole((int)$user['id']);

        $this->view('student.profile.index', [
            'title'      => 'My Profile — LMSAdvisor',
            'page_title' => 'My Profile',
            'auth_user'  => $user,
            'full_user'  => $fullUser,
            'flash'      => $this->getFlash(),
            'enrolled'   => $enrolled,
            'points'     => $points,
            'csrf_token' => \App\Middleware\CsrfMiddleware::token(),
        ], 'student');
    }

    // ── POST /learn/profile/update ────────────────────────────────────────────
    public function updateProfile(array $params): void
    {
        $this->guard();
        \App\Middleware\CsrfMiddleware::verify();

        $user      = AuthService::user();
        $userModel = new \App\Models\User();

        $firstName = \App\Helpers\Sanitizer::string($this->request->post('first_name', ''), 80);
        $lastName  = \App\Helpers\Sanitizer::string($this->request->post('last_name', ''), 80);
        $email     = \App\Helpers\Sanitizer::email($this->request->post('email', ''));

        $v = (new \App\Helpers\Validator())
            ->required('first_name', $firstName, 'First name')
            ->required('last_name',  $lastName,  'Last name')
            ->required('email',      $email,     'Email')
            ->email('email',         $email);

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/learn/profile');
        }

        // Check email not taken by another user
        $existing = $userModel->findByEmail($email);
        if ($existing && (int)$existing['id'] !== (int)$user['id']) {
            $this->flash('error', 'That email address is already in use.');
            $this->redirect('/learn/profile');
        }

        $userModel->update((int)$user['id'], [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
        ]);

        // Refresh session name
        $_SESSION['user_name']  = $firstName . ' ' . $lastName;
        $_SESSION['user_email'] = $email;

        \App\Models\AuditLog::write('user.profile_update', 'user', (int)$user['id']);
        $this->flash('success', 'Profile updated successfully.');
        $this->redirect('/learn/profile');
    }

    // ── POST /learn/profile/change-password ───────────────────────────────────
    public function changePassword(array $params): void
    {
        $this->guard();
        \App\Middleware\CsrfMiddleware::verify();

        $user      = AuthService::user();
        $userModel = new \App\Models\User();
        $fullUser  = $userModel->find((int)$user['id']);

        $current  = $this->request->post('current_password', '');
        $new      = $this->request->post('new_password', '');
        $confirm  = $this->request->post('confirm_password', '');

        if (!$current || !$new || !$confirm) {
            $this->flash('error', 'All password fields are required.');
            $this->redirect('/learn/profile');
        }

        // Verify current password
        if (!password_verify($current, $fullUser['password_hash'] ?? '')) {
            $this->flash('error', 'Current password is incorrect.');
            $this->redirect('/learn/profile');
        }

        if (strlen($new) < 8) {
            $this->flash('error', 'New password must be at least 8 characters.');
            $this->redirect('/learn/profile');
        }

        if ($new !== $confirm) {
            $this->flash('error', 'New passwords do not match.');
            $this->redirect('/learn/profile');
        }

        $userModel->update((int)$user['id'], [
            'password_hash' => password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        \App\Models\AuditLog::write('user.password_change', 'user', (int)$user['id']);
        $this->flash('success', 'Password changed successfully. Please use your new password next time you log in.');
        $this->redirect('/learn/profile');
    }

    // ── GET /learn/certificate/:enrollmentId ──────────────────────────────────
    public function certificate(array $params): void
    {
        // Delegate to CertificateController::view
        $certController = new \App\Controllers\Student\CertificateController(
            $this->request, $this->response
        );
        $certController->show($params);
    }
}
