<?php declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Database;
use App\Helpers\Sanitizer;
class ForumApiController extends AuthController {
    // GET /api/v1/courses/:uuid/forum/threads
    public function threads(array $params): void {
        $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $page = max(1,(int)$this->request->get('page',1)); $perPage=20;
        $stmt = $pdo->prepare('SELECT ft.*,u.first_name,u.last_name,(SELECT COUNT(*) FROM forum_replies fr WHERE fr.thread_id=ft.id) AS reply_count FROM forum_threads ft JOIN users u ON u.id=ft.user_id JOIN courses c ON c.id=ft.course_id WHERE c.uuid=? ORDER BY ft.is_pinned DESC,ft.created_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$params['uuid']??'',$perPage,($page-1)*$perPage]);
        $this->json(['data'=>$stmt->fetchAll(),'page'=>$page]);
    }
    // GET /api/v1/forum/threads/:id
    public function thread(array $params): void {
        $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT ft.*,u.first_name,u.last_name FROM forum_threads ft JOIN users u ON u.id=ft.user_id WHERE ft.id=? LIMIT 1');
        $stmt->execute([$params['id']??0]);
        $t = $stmt->fetch(); if (!$t) { http_response_code(404); $this->json(['error'=>'Not found.']); }
        $replies = $pdo->prepare('SELECT fr.*,u.first_name,u.last_name FROM forum_replies fr JOIN users u ON u.id=fr.user_id WHERE fr.thread_id=? ORDER BY fr.created_at ASC');
        $replies->execute([$t['id']]);
        $t['replies'] = $replies->fetchAll();
        $this->json(['data'=>$t]);
    }
    // POST /api/v1/courses/:uuid/forum/threads
    public function createThread(array $params): void {
        $user = $this->apiAuth('write');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id FROM courses WHERE uuid=? LIMIT 1'); $stmt->execute([$params['uuid']??'']); $c=$stmt->fetch();
        if (!$c) { http_response_code(404); $this->json(['error'=>'Course not found.']); }
        $title = Sanitizer::string($this->request->post('title',''),255);
        $body  = trim($this->request->post('body',''));
        if (!$title||!$body) { http_response_code(422); $this->json(['error'=>'title and body required.']); }
        $pdo->prepare('INSERT INTO forum_threads (course_id,user_id,title,body) VALUES (?,?,?,?)')->execute([$c['id'],$user['id'],$title,$body]);
        $this->json(['success'=>true,'id'=>(int)$pdo->lastInsertId()],201);
    }
}
