<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\CollaborationService;
use App\Helpers\Sanitizer;

class CollaborationApiController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
    }

    // ── Notes ──────────────────────────────────────────────────────────────

    public function getNotes(array $p): void
    {
        $user  = AuthService::user();
        $notes = CollaborationService::getNotes((int)$user['id'], (int)$p['lesson_id']);
        $this->json(['success'=>true,'notes'=>$notes]);
    }

    public function saveNote(array $p): void
    {
        CsrfMiddleware::verify();
        $user  = AuthService::user();
        $note  = Sanitizer::string($this->request->post('note',''), 2000);
        $ts    = (int)$this->request->post('timestamp_sec', 0);
        $cid   = (int)$this->request->post('course_id', 0);
        if (!$note) { $this->json(['success'=>false,'message'=>'Note cannot be empty.']); }

        $id = CollaborationService::saveNote((int)$user['id'], (int)$p['lesson_id'], $cid, $note, $ts);
        $this->json(['success'=>true,'id'=>$id,'message'=>'Note saved.']);
    }

    public function deleteNote(array $p): void
    {
        CsrfMiddleware::verify();
        $user = AuthService::user();
        CollaborationService::deleteNote((int)$p['note_id'], (int)$user['id']);
        $this->json(['success'=>true]);
    }

    public function exportNotes(array $p): void
    {
        $user    = AuthService::user();
        $courseId= (int)$p['course_id'];
        $json    = CollaborationService::exportNotes((int)$user['id'], $courseId);
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="notes-course-' . $courseId . '.json"');
        echo $json;
        exit;
    }

    // ── Comments ───────────────────────────────────────────────────────────

    public function getComments(array $p): void
    {
        $comments = CollaborationService::getComments((int)$p['lesson_id']);
        $this->json(['success'=>true,'comments'=>$comments]);
    }

    public function addComment(array $p): void
    {
        CsrfMiddleware::verify();
        $user     = AuthService::user();
        $body     = Sanitizer::string($this->request->post('body',''), 2000);
        $parentId = $this->request->post('parent_id') ? (int)$this->request->post('parent_id') : null;
        if (!$body) { $this->json(['success'=>false,'message'=>'Comment cannot be empty.']); }

        $id = CollaborationService::addComment((int)$p['lesson_id'], (int)$user['id'], $body, $parentId);
        $this->json(['success'=>true,'id'=>$id,'message'=>'Comment posted.']);
    }

    public function deleteComment(array $p): void
    {
        CsrfMiddleware::verify();
        $user = AuthService::user();
        CollaborationService::deleteComment((int)$p['comment_id'], (int)$user['id'], $user['role'] ?? 'student');
        $this->json(['success'=>true]);
    }

    public function pinComment(array $p): void
    {
        CsrfMiddleware::verify();
        $pin = (bool)$this->request->post('pin', 1);
        CollaborationService::pinComment((int)$p['comment_id'], $pin);
        $this->json(['success'=>true]);
    }

    // ── Ask a question → forum thread ────────────────────────────────────

    public function askQuestion(array $p): void
    {
        CsrfMiddleware::verify();
        $user  = AuthService::user();
        $title = Sanitizer::string($this->request->post('title',''), 255);
        $body  = Sanitizer::string($this->request->post('body',''), 5000);
        if (!$title) { $this->json(['success'=>false,'message'=>'Please enter a question title.']); }

        $threadId = CollaborationService::askQuestion((int)$p['lesson_id'], (int)$user['id'], $title, $body);
        $this->json(['success'=>true,'thread_id'=>$threadId,'message'=>'Question posted to the course forum.']);
    }
}
