<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\GradingService;
use App\Models\Quiz;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\AuditLog;

class QuizController extends Controller
{
    private Quiz       $quiz;
    private Enrollment $enrollModel;
    private Course     $courseModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle('/login');
        $this->quiz        = new Quiz();
        $this->enrollModel = new Enrollment();
        $this->courseModel = new Course();
    }

    // ── GET /learn/courses/:uuid/quiz/:lessonId ───────────────────────────────
    public function show(array $params): void
    {
        $user     = AuthService::user();
        $course   = $this->getCourse($params['uuid'] ?? '');
        $lessonId = (int)($params['lessonId'] ?? 0);

        // Verify enrollment
        $enrollment = $this->enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);
        if (!$enrollment) {
            $this->flash('error', 'You are not enrolled in this course.');
            $this->redirect('/learn/courses');
        }

        // Load quiz for this lesson
        $quiz = $this->quiz->findByLesson($lessonId);
        if (!$quiz) {
            $this->flash('error', 'Quiz not found for this lesson.');
            $this->redirect('/learn/courses/' . $course['uuid'] . '/learn?lesson=' . $lessonId);
        }

        $quiz['questions'] = $this->quiz->questionsWithOptions($quiz['id']);

        // Shuffle if enabled
        if ($quiz['shuffle_questions']) {
            shuffle($quiz['questions']);
        }
        if ($quiz['shuffle_options']) {
            foreach ($quiz['questions'] as &$q) {
                if ($q['type'] !== 'true_false') {
                    shuffle($q['options']);
                }
            }
        }

        // Attempts info
        $attemptCount = $this->quiz->countAttempts($quiz['id'], (int)$user['id']);
        $maxAttempts  = (int)$quiz['max_attempts'];
        $bestScore    = $this->quiz->bestScore($quiz['id'], (int)$user['id']);
        $pastAttempts = $this->quiz->attemptsForUser($quiz['id'], (int)$user['id']);

        $canAttempt = $maxAttempts === 0 || $attemptCount < $maxAttempts;

        $this->view('student.quiz.show', [
            'title'        => $quiz['title'] . ' — LMSAdvisor',
            'page_title'   => $quiz['title'],
            'auth_user'    => $user,
            'flash'        => $this->getFlash(),
            'course'       => $course,
            'quiz'         => $quiz,
            'lessonId'     => $lessonId,
            'enrollment'   => $enrollment,
            'attemptCount' => $attemptCount,
            'maxAttempts'  => $maxAttempts,
            'bestScore'    => $bestScore,
            'pastAttempts' => $pastAttempts,
            'canAttempt'   => $canAttempt,
            'csrf_token'   => CsrfMiddleware::token(),
        ], 'student');
    }

    // ── POST /learn/courses/:uuid/quiz/:lessonId/submit ───────────────────────
    public function submit(array $params): void
    {
        CsrfMiddleware::verify();

        $user     = AuthService::user();
        $course   = $this->getCourse($params['uuid'] ?? '');
        $lessonId = (int)($params['lessonId'] ?? 0);

        $enrollment = $this->enrollModel->findEnrollment((int)$course['id'], (int)$user['id']);
        if (!$enrollment) {
            $this->redirect('/learn/courses');
        }

        $quiz = $this->quiz->findByLesson($lessonId);
        if (!$quiz) {
            $this->redirect('/learn/courses/' . $course['uuid'] . '/learn?lesson=' . $lessonId);
        }

        // Check attempts
        $attemptCount = $this->quiz->countAttempts($quiz['id'], (int)$user['id']);
        $maxAttempts  = (int)$quiz['max_attempts'];
        if ($maxAttempts > 0 && $attemptCount >= $maxAttempts) {
            $this->flash('error', 'You have used all your attempts for this quiz.');
            $this->redirect('/learn/courses/' . $course['uuid'] . '/quiz/' . $lessonId);
        }

        // Load questions with options for grading
        $questions = $this->quiz->questionsWithOptions($quiz['id']);

        // Collect submitted answers: answers[questionId] = value|array
        $submitted = [];
        $raw = $this->request->post('answers', []);
        if (is_array($raw)) {
            foreach ($raw as $qid => $val) {
                $submitted[(int)$qid] = $val;
            }
        }

        // Grade
        $result   = GradingService::grade($questions, $submitted, (int)$quiz['pass_percentage']);
        $timeSec  = max(0, (int)$this->request->post('time_taken_sec', 0));

        // Record attempt
        $attemptId = $this->quiz->startAttempt($quiz['id'], (int)$user['id'], (int)$enrollment['id']);
        $this->quiz->completeAttempt(
            $attemptId,
            $result['score'],
            $result['passed'],
            $result['results'],
            $timeSec
        );

        // If passed → mark lesson as complete
        if ($result['passed']) {
            $this->enrollModel->markLessonProgress(
                (int)$enrollment['id'], $lessonId, 'completed', 100
            );
        }

        AuditLog::write('quiz.attempt', 'quiz', $quiz['id'], null, [
            'score'  => $result['score'],
            'passed' => $result['passed'],
        ]);

        // Store result in session for the results page
        $_SESSION['quiz_result'] = [
            'attempt_id' => $attemptId,
            'quiz_id'    => $quiz['id'],
            'lesson_id'  => $lessonId,
            'course_uuid'=> $course['uuid'],
            'score'      => $result['score'],
            'passed'     => $result['passed'],
            'points_earned'   => $result['pointsEarned'],
            'points_possible' => $result['pointsPossible'],
            'results'    => $result['results'],
            'questions'  => $questions,
            'show_answers' => (bool)$quiz['show_answers_after'],
            'time_sec'   => $timeSec,
            'pass_pct'   => (int)$quiz['pass_percentage'],
        ];

        $this->redirect('/learn/courses/' . $course['uuid'] . '/quiz/' . $lessonId . '/result');
    }

    // ── GET /learn/courses/:uuid/quiz/:lessonId/result ────────────────────────
    public function result(array $params): void
    {
        $user   = AuthService::user();
        $course = $this->getCourse($params['uuid'] ?? '');

        $quizResult = $_SESSION['quiz_result'] ?? null;
        unset($_SESSION['quiz_result']);

        if (!$quizResult) {
            $this->redirect('/learn/courses/' . $course['uuid'] . '/learn');
        }

        $lessonId     = (int)($params['lessonId'] ?? $quizResult['lesson_id']);
        $quiz         = $this->quiz->findByLesson($lessonId);
        $attemptCount = $this->quiz->countAttempts($quiz['id'], (int)$user['id']);
        $maxAttempts  = (int)$quiz['max_attempts'];
        $canRetry     = $maxAttempts === 0 || $attemptCount < $maxAttempts;

        $this->view('student.quiz.result', [
            'title'      => 'Quiz Result — LMSAdvisor',
            'page_title' => 'Quiz Result',
            'auth_user'  => $user,
            'flash'      => $this->getFlash(),
            'course'     => $course,
            'quiz'       => $quiz,
            'lessonId'   => $lessonId,
            'result'     => $quizResult,
            'canRetry'   => $canRetry,
            'attemptCount' => $attemptCount,
            'maxAttempts'  => $maxAttempts,
        ], 'student');
    }

    // ── Helper ────────────────────────────────────────────────────────────────
    private function getCourse(string $uuid): array
    {
        $course = $this->courseModel->findByUuidFull($uuid);
        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->redirect('/learn/courses');
        }
        return $course;
    }
}
