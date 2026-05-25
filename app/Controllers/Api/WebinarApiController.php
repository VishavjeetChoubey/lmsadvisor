<?php declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Database;
class WebinarApiController extends AuthController {
    // GET /api/v1/webinars
    public function index(array $params): void {
        $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT w.uuid,w.title,w.provider,w.scheduled_at,w.duration_min,w.status,c.title AS course_title,c.uuid AS course_uuid FROM webinar_sessions w LEFT JOIN courses c ON c.id=w.course_id WHERE w.status IN ('scheduled','live') ORDER BY w.scheduled_at ASC LIMIT 50");
        $this->json(['data'=>$stmt->fetchAll()]);
    }
    // GET /api/v1/webinars/:uuid
    public function show(array $params): void {
        $user = $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM webinar_sessions WHERE uuid=? LIMIT 1');
        $stmt->execute([$params['uuid']??'']);
        $w = $stmt->fetch(); if (!$w) { http_response_code(404); $this->json(['error'=>'Not found.']); }
        $out = ['uuid'=>$w['uuid'],'title'=>$w['title'],'provider'=>$w['provider'],'scheduled_at'=>$w['scheduled_at'],'duration_min'=>$w['duration_min'],'status'=>$w['status'],'join_url'=>$w['join_url']];
        $this->json(['data'=>$out]);
    }
}
