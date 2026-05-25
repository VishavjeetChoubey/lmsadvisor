<?php declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Database;
use App\Helpers\Sanitizer;
class ReviewApiController extends AuthController {
    // GET /api/v1/courses/:uuid/reviews
    public function index(array $params): void {
        $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT cr.id,cr.rating,cr.comment,cr.created_at,u.first_name,u.last_name FROM course_reviews cr JOIN courses c ON c.id=cr.course_id JOIN users u ON u.id=cr.user_id WHERE c.uuid=? AND cr.is_approved=1 ORDER BY cr.created_at DESC LIMIT 50');
        $stmt->execute([$params['uuid']??'']);
        $this->json(['data'=>$stmt->fetchAll()]);
    }
    // POST /api/v1/courses/:uuid/reviews
    public function store(array $params): void {
        $user = $this->apiAuth('write');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id FROM courses WHERE uuid=? LIMIT 1'); $stmt->execute([$params['uuid']??'']); $c=$stmt->fetch();
        if (!$c) { http_response_code(404); $this->json(['error'=>'Not found.']); }
        $existing = $pdo->prepare('SELECT id FROM course_reviews WHERE course_id=? AND user_id=? LIMIT 1'); $existing->execute([$c['id'],$user['id']]);
        if ($existing->fetch()) { http_response_code(409); $this->json(['error'=>'Already reviewed.','code'=>'DUPLICATE_REVIEW']); }
        $rating  = min(5,max(1,(int)$this->request->post('rating',5)));
        $comment = Sanitizer::string($this->request->post('comment',''),1000);
        $pdo->prepare('INSERT INTO course_reviews (course_id,user_id,rating,comment) VALUES (?,?,?,?)')->execute([$c['id'],$user['id'],$rating,$comment]);
        $this->json(['success'=>true,'message'=>'Review submitted for approval.'],201);
    }
}
