<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Models\Quiz;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\AuditLog;
use App\Helpers\Sanitizer;
use App\Helpers\Validator;

class QuizController extends Controller
{
    private Quiz   $quiz;
    private Lesson $lesson;
    private Course $course;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
        $this->quiz   = new Quiz();
        $this->lesson = new Lesson();
        $this->course = new Course();
    }

    // ── GET /admin/quizzes/lesson/:lessonId ───────────────────────────────────
    // Full quiz builder page for a lesson
    // GET /admin/quizzes — list all quizzes
    public function listPage(array $params): void
    {
        $pdo   = \App\Core\Database::getInstance();
        $quizzes = $pdo->query(
            'SELECT q.*, l.title AS lesson_title, c.title AS course_title, c.uuid AS course_uuid
             FROM quizzes q
             JOIN lessons l ON l.id = q.lesson_id
             JOIN courses c ON c.id = l.course_id
             ORDER BY q.id DESC LIMIT 100'
        )->fetchAll();

        $this->view('admin.quizzes.list', [
            'title'       => 'Quizzes — LMSAdvisor',
            'page_title'  => 'Quizzes',
            'breadcrumbs' => [['label' => 'Quizzes']],
            'flash'       => $this->getFlash(),
            'auth_user'   => \App\Services\AuthService::user(),
            'csrf_token'  => \App\Middleware\CsrfMiddleware::token(),
            'quizzes'     => $quizzes,
        ]);
    }

    public function builder(array $params): void
    {
        $lessonId = (int)($params['lessonId'] ?? 0);
        $lesson   = $this->lesson->find($lessonId);

        if (!$lesson || $lesson['type'] !== 'quiz') {
            $this->flash('error', 'Quiz lesson not found.');
            $this->redirect('/admin/courses');
        }

        // Load the course for breadcrumbs
        $pdo    = \App\Core\Database::getInstance();
        $stmt   = $pdo->prepare(
            'SELECT c.uuid, c.title FROM courses c
             JOIN lessons l ON l.course_id = c.id
             WHERE l.id = ? LIMIT 1'
        );
        $stmt->execute([$lessonId]);
        $courseRow = $stmt->fetch() ?: [];

        // Get or create quiz record for this lesson
        $quiz = $this->quiz->findByLesson($lessonId);
        if (!$quiz) {
            $quizId = $this->quiz->create([
                'lesson_id'         => $lessonId,
                'title'             => $lesson['title'],
                'description'       => null,
                'time_limit_sec'    => null,
                'pass_percentage'   => 70,
                'shuffle_questions' => 0,
                'shuffle_options'   => 0,
                'show_answers_after'=> 1,
                'max_attempts'      => 3,
            ]);
            $quiz = $this->quiz->findWithQuestions($quizId);
        } else {
            $quiz['questions'] = $this->quiz->questionsWithOptions($quiz['id']);
        }

        $this->view('admin.quizzes.builder', [
            'title'       => 'Quiz Builder — LMSAdvisor',
            'page_title'  => 'Quiz Builder',
            'breadcrumbs' => [
                ['label' => 'Courses', 'url' => '/admin/courses'],
                ['label' => $courseRow['title'] ?? 'Course', 'url' => '/admin/courses/' . ($courseRow['uuid'] ?? '') . '/edit'],
                ['label' => $lesson['title']],
                ['label' => 'Quiz Builder'],
            ],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'quiz'        => $quiz,
            'lesson'      => $lesson,
            'courseUuid'  => $courseRow['uuid'] ?? '',
            'stats'       => $this->quiz->stats($quiz['id']),
        ]);
    }

    // ── POST /admin/quizzes/:id/settings ──────────────────────────────────────
    public function saveSettings(array $params): void
    {
        CsrfMiddleware::verify();
        $quizId = (int)($params['id'] ?? 0);

        $title = Sanitizer::string($this->request->post('title', ''), 255);
        $v = (new Validator())->required('title', $title, 'Quiz title');
        if ($v->fails()) {
            $this->json(['success' => false, 'message' => $v->firstError()]);
        }

        $this->quiz->update($quizId, [
            'title'              => $title,
            'description'        => $this->request->post('description', ''),
            'time_limit_sec'     => $this->request->post('time_limit_sec', ''),
            'pass_percentage'    => $this->request->post('pass_percentage', 70),
            'shuffle_questions'  => $this->request->post('shuffle_questions', 0),
            'shuffle_options'    => $this->request->post('shuffle_options', 0),
            'show_answers_after' => $this->request->post('show_answers_after', 1),
            'max_attempts'       => $this->request->post('max_attempts', 3),
            'is_required'        => (int)$this->request->post('is_required', 0),
        ]);

        AuditLog::write('quiz.update_settings', 'quiz', $quizId);
        $this->json(['success' => true]);
    }

    // ── POST /admin/quizzes/:id/questions ─────────────────────────────────────
    public function addQuestion(array $params): void
    {
        CsrfMiddleware::verify();
        $quizId = (int)($params['id'] ?? 0);

        $type  = Sanitizer::string($this->request->post('type', 'single'), 20);
        $count = count($this->quiz->questionsWithOptions($quizId));

        $questionId = $this->quiz->addQuestion([
            'quiz_id'     => $quizId,
            'question'    => 'New Question',
            'explanation' => null,
            'type'        => $type,
            'points'      => 1,
            'sort_order'  => $count,
        ]);

        // Add default options based on type
        $defaults = $this->defaultOptions($type);
        $this->quiz->setOptions($questionId, $defaults);

        $this->json([
            'success'     => true,
            'question_id' => $questionId,
            'type'        => $type,
        ]);
    }

    // ── POST /admin/questions/:id/save ────────────────────────────────────────
    public function saveQuestion(array $params): void
    {
        CsrfMiddleware::verify();
        $questionId = (int)($params['id'] ?? 0);

        $questionText = $this->request->post('question', '');
        $v = (new Validator())->required('question', $questionText, 'Question text');
        if ($v->fails()) {
            $this->json(['success' => false, 'message' => $v->firstError()]);
        }

        $type = Sanitizer::string($this->request->post('type', 'single'), 20);

        // Extra JSON fields for new question types
        $orderItems        = null;
        $acceptableAnswers = null;
        $matchPairs        = null;

        if ($type === 'ordering') {
            $raw = $this->request->post('order_items', '[]');
            $orderItems = is_string($raw) ? $raw : json_encode([]);
        } elseif ($type === 'short_answer') {
            $raw = $this->request->post('acceptable_answers', '[]');
            $acceptableAnswers = is_string($raw) ? $raw : json_encode([]);
        } elseif ($type === 'matching') {
            $raw = $this->request->post('match_pairs', '[]');
            $matchPairs = is_string($raw) ? $raw : json_encode([]);
        }

        $this->quiz->updateQuestion($questionId, [
            'question'           => $questionText,
            'explanation'        => $this->request->post('explanation', null),
            'type'               => $type,
            'points'             => max(1, (int)$this->request->post('points', 1)),
            'sort_order'         => (int)$this->request->post('sort_order', 0),
            'order_items'        => $orderItems,
            'acceptable_answers' => $acceptableAnswers,
            'match_pairs'        => $matchPairs,
        ]);

        // Rebuild options
        $optionTexts    = $this->request->post('option_text', []);
        $correctFlags   = $this->request->post('is_correct', []);

        // New types store data in JSON columns, not question_options
        $newTypes = ['ordering', 'short_answer', 'matching'];
        if (in_array($type, $newTypes, true)) {
            // Delete any stale options from previous type
            $this->quiz->setOptions($questionId, []);
            $this->json(['success' => true]);
        }

        if (!is_array($optionTexts)) {
            $this->json(['success' => false, 'message' => 'Invalid options data.']);
        }

        // For single/true_false: correct is a single radio index
        // For multiple: correct is an array of indices
        // For fill_blank: is_correct comes as a hidden = 1 for each option
        $options = [];
        foreach ($optionTexts as $i => $text) {
            if (trim($text) === '') continue;

            $isCorrect = false;
            if ($type === 'single' || $type === 'true_false') {
                // correct = the index selected by radio
                $isCorrect = ((string)($correctFlags['radio'] ?? '') === (string)$i);
            } elseif ($type === 'multiple') {
                $isCorrect = !empty($correctFlags[$i]);
            } elseif ($type === 'fill_blank') {
                $isCorrect = !empty($correctFlags[$i]);
            }

            $options[] = ['text' => trim($text), 'is_correct' => $isCorrect];
        }

        if (empty($options)) {
            $this->json(['success' => false, 'message' => 'At least one option is required.']);
        }

        // Validate at least one correct answer
        $hasCorrect = array_filter($options, fn($o) => $o['is_correct']);
        if (empty($hasCorrect)) {
            $this->json(['success' => false, 'message' => 'Mark at least one correct answer.']);
        }

        $this->quiz->setOptions($questionId, $options);
        $this->json(['success' => true]);
    }

    // ── POST /admin/questions/:id/delete ─────────────────────────────────────
    public function deleteQuestion(array $params): void
    {
        CsrfMiddleware::verify();
        $this->quiz->deleteQuestion((int)($params['id'] ?? 0));
        $this->json(['success' => true]);
    }

    // ── POST /admin/quizzes/:id/reorder ──────────────────────────────────────
    public function reorderQuestions(array $params): void
    {
        CsrfMiddleware::verify();
        $ids = $this->request->post('ids', []);
        if (is_array($ids)) {
            $this->quiz->reorderQuestions(array_map('intval', $ids));
        }
        $this->json(['success' => true]);
    }

    // ── GET /admin/quizzes/:id/preview ────────────────────────────────────────
    public function preview(array $params): void
    {
        $quizId = (int)($params['id'] ?? 0);
        $quiz   = $this->quiz->findWithQuestions($quizId);

        if (!$quiz) {
            $this->flash('error', 'Quiz not found.');
            $this->redirect('/admin/courses');
        }

        $this->view('admin.quizzes.preview', [
            'title'       => 'Quiz Preview — LMSAdvisor',
            'page_title'  => 'Quiz Preview: ' . $quiz['title'],
            'breadcrumbs' => [
                ['label' => 'Courses', 'url' => '/admin/courses'],
                ['label' => 'Quiz Preview'],
            ],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'quiz'        => $quiz,
            'csrf_token'  => CsrfMiddleware::token(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function defaultOptions(string $type): array
    {
        return match($type) {
            'true_false' => [
                ['text' => 'True',  'is_correct' => true],
                ['text' => 'False', 'is_correct' => false],
            ],
            'fill_blank' => [
                ['text' => 'Correct Answer', 'is_correct' => true],
            ],
            default => [ // single, multiple
                ['text' => 'Option A', 'is_correct' => true],
                ['text' => 'Option B', 'is_correct' => false],
                ['text' => 'Option C', 'is_correct' => false],
                ['text' => 'Option D', 'is_correct' => false],
            ],
        };
    }
}
