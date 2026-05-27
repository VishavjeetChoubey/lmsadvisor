<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\AiTutorService;
use App\Core\Database;
use App\Helpers\Sanitizer;

class AiTutorController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
    }

    /** POST /api/ai/chat — student chatbot for a lesson */
    public function chat(array $p): void
    {
        CsrfMiddleware::verify();
        $user      = AuthService::user();
        $courseId  = (int)$this->request->post('course_id', 0);
        $lessonId  = (int)$this->request->post('lesson_id', 0);
        $question  = Sanitizer::string($this->request->post('question', ''), 1000);

        if (!$question) { $this->json(['success'=>false,'message'=>'Please enter a question.']); }

        // Load course + lesson context
        $pdo  = Database::getInstance();
        $cStmt= $pdo->prepare('SELECT title FROM courses WHERE id=? LIMIT 1');
        $cStmt->execute([$courseId]); $course = $cStmt->fetch();
        $lStmt= $pdo->prepare('SELECT title, content FROM lessons WHERE id=? LIMIT 1');
        $lStmt->execute([$lessonId]); $lesson = $lStmt->fetch();

        try {
            $result = AiTutorService::chat(
                (int)$user['id'], $courseId, $lessonId, $question,
                [
                    'course_title'   => $course['title'] ?? '',
                    'lesson_title'   => $lesson['title'] ?? '',
                    'lesson_content' => $lesson['content'] ?? '',
                ]
            );
            $this->json(['success'=>true,'answer'=>$result['answer']]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    /** POST /api/ai/summarise — summarise a lesson */
    public function summarise(array $p): void
    {
        CsrfMiddleware::verify();
        $lessonId = (int)$this->request->post('lesson_id', 0);
        $pdo      = Database::getInstance();
        $stmt     = $pdo->prepare('SELECT title, content FROM lessons WHERE id=? LIMIT 1');
        $stmt->execute([$lessonId]);
        $lesson   = $stmt->fetch();
        if (!$lesson) { $this->json(['success'=>false,'message'=>'Lesson not found.']); }

        try {
            $result = AiTutorService::summariseLesson($lesson['content'] ?? '', $lesson['title']);
            $this->json(['success'=>true,'bullets'=>$result['bullets']]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    /** POST /api/ai/generate-questions — generate quiz from lesson */
    public function generateQuestions(array $p): void
    {
        CsrfMiddleware::verify();
        $lessonId = (int)$this->request->post('lesson_id', 0);
        $count    = max(3, min(10, (int)$this->request->post('count', 5)));
        $pdo      = Database::getInstance();
        $stmt     = $pdo->prepare('SELECT title, content FROM lessons WHERE id=? LIMIT 1');
        $stmt->execute([$lessonId]);
        $lesson   = $stmt->fetch();
        if (!$lesson) { $this->json(['success'=>false,'message'=>'Lesson not found.']); }

        try {
            $questions = AiTutorService::generateQuizQuestions(
                $lesson['content'] ?? '', $lesson['title'], $count
            );
            $this->json(['success'=>true,'questions'=>$questions,'count'=>count($questions)]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    /** POST /api/ai/translate — translate lesson content */
    public function translate(array $p): void
    {
        CsrfMiddleware::verify();
        $lessonId = (int)$this->request->post('lesson_id', 0);
        $lang     = Sanitizer::string($this->request->post('language', 'Hindi'), 50);
        $pdo      = Database::getInstance();
        $stmt     = $pdo->prepare('SELECT content FROM lessons WHERE id=? LIMIT 1');
        $stmt->execute([$lessonId]);
        $lesson   = $stmt->fetch();
        if (!$lesson) { $this->json(['success'=>false,'message'=>'Lesson not found.']); }

        try {
            $translated = AiTutorService::translateLesson($lesson['content'] ?? '', $lang);
            $this->json(['success'=>true,'content'=>$translated,'language'=>$lang]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    /** POST /api/ai/improve-writing */
    public function improveWriting(array $p): void
    {
        CsrfMiddleware::verify();
        $text    = Sanitizer::string($this->request->post('text', ''), 3000);
        $context = Sanitizer::string($this->request->post('context', 'forum post'), 50);
        if (!$text) { $this->json(['success'=>false,'message'=>'Please enter some text.']); }
        try {
            $improved = AiTutorService::improveWriting($text, $context);
            $this->json(['success'=>true,'improved'=>$improved]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    /** GET /api/ai/recommend-paths */
    public function recommendPaths(array $p): void
    {
        $user = AuthService::user();
        try {
            $paths = AiTutorService::recommendPaths((int)$user['id']);
            $this->json(['success'=>true,'paths'=>$paths]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }
}
