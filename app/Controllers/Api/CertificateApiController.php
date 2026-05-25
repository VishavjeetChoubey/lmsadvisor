<?php declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Database;
use App\Services\CertificateService;
class CertificateApiController extends AuthController {
    // GET /api/v1/certificates  (my certificates)
    public function index(array $params): void {
        $user = $this->apiAuth('read');
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT c.uuid,c.issued_at,co.title AS course_title,co.uuid AS course_uuid FROM certificates c JOIN courses co ON co.id=c.course_id WHERE c.user_id=? ORDER BY c.issued_at DESC');
        $stmt->execute([$user['id']]);
        $certs = $stmt->fetchAll();
        foreach ($certs as &$c) {
            $c['verify_url'] = APP_URL . '/certificate/verify/' . $c['uuid'];
        }
        $this->json(['data'=>$certs]);
    }
    // GET /api/v1/certificates/:uuid/verify  (public)
    public function verify(array $params): void {
        $this->setCorsHeaders();
        $cert = CertificateService::findByUuid($params['uuid']??'');
        if (!$cert) { http_response_code(404); $this->json(['error'=>'Certificate not found.','valid'=>false]); }
        $this->json(['valid'=>true,'data'=>['student_name'=>$cert['first_name'].' '.$cert['last_name'],'course_title'=>$cert['course_title'],'completed_on'=>$cert['completed_at'],'issued_at'=>$cert['issued_at'],'certificate_id'=>strtoupper(substr($cert['uuid'],0,8))]]);
    }
}
