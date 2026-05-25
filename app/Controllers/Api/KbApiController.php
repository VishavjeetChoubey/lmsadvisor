<?php declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Database;
use App\Helpers\Sanitizer;
class KbApiController extends AuthController {
    // GET /api/v1/kb/articles
    public function index(array $params): void {
        $this->apiAuth('read');
        $pdo    = Database::getInstance();
        $search = Sanitizer::string($this->request->get('search',''),100);
        $where  = ["a.status='published'"]; $binds = [];
        if ($search) { $where[] = '(a.title LIKE ? OR a.body LIKE ?)'; $binds[] = "%$search%"; $binds[] = "%$search%"; }
        $ws = implode(' AND ',$where);
        $stmt = $pdo->prepare("SELECT a.uuid,a.title,a.slug,a.created_at,c.name AS category FROM kb_articles a LEFT JOIN kb_categories c ON c.id=a.category_id WHERE $ws ORDER BY a.updated_at DESC LIMIT 50");
        $stmt->execute($binds);
        $this->json(['data'=>$stmt->fetchAll()]);
    }
    // GET /api/v1/kb/articles/:uuid
    public function show(array $params): void {
        $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT a.*,c.name AS category FROM kb_articles a LEFT JOIN kb_categories c ON c.id=a.category_id WHERE a.uuid=? AND a.status='published' LIMIT 1");
        $stmt->execute([$params['uuid']??'']);
        $a = $stmt->fetch(); if (!$a) { http_response_code(404); $this->json(['error'=>'Not found.']); }
        $pdo->prepare('UPDATE kb_articles SET views=views+1 WHERE uuid=?')->execute([$a['uuid']]);
        $this->json(['data'=>$a]);
    }
}
