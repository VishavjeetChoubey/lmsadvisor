<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Models\Enrollment;
use App\Helpers\Sanitizer;

class CourseApiController extends AuthController
{
    // GET /api/v1/courses
    public function index(array $params): void
    {
        $this->apiAuth();
        $pdo    = Database::getInstance();
        $search = Sanitizer::string($this->request->get('search',''), 100);
        $catId  = (int)$this->request->get('cat', 0);
        $page   = max(1, (int)$this->request->get('page', 1));

        $where = ["c.status='published'"]; $binds = [];
        if ($search) { $where[] = 'c.title LIKE ?'; $binds[] = "%$search%"; }
        if ($catId)  { $where[] = 'c.category_id = ?'; $binds[] = $catId; }
        $ws     = implode(' AND ', $where);
        $offset = ($page-1)*20;

        $countS = $pdo->prepare("SELECT COUNT(*) FROM courses c WHERE $ws");
        $countS->execute($binds);
        $total  = (int)$countS->fetchColumn();

        $stmt = $pdo->prepare("SELECT c.uuid,c.title,c.short_description,c.level,c.language,c.duration_hours,c.grade_points,c.thumbnail,cat.name AS category,
          (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS enrollment_count
          FROM courses c LEFT JOIN categories cat ON cat.id=c.category_id WHERE $ws ORDER BY c.published_at DESC LIMIT 20 OFFSET $offset");
        $stmt->execute($binds);
        $this->json(['data'=>$stmt->fetchAll(),'total'=>$total,'page'=>$page,'per_page'=>20]);
    }

    // GET /api/v1/courses/:uuid
    public function show(array $params): void
    {
        $this->apiAuth();
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM courses WHERE uuid=? AND status="published" LIMIT 1');
        $stmt->execute([$params['uuid']??'']);
        $course = $stmt->fetch();
        if (!$course) { http_response_code(404); $this->json(['error'=>'Course not found.']); }

        // Sections + lessons
        $secs = $pdo->prepare('SELECT s.*,
          (SELECT JSON_ARRAYAGG(JSON_OBJECT("id",l.id,"title",l.title,"type",l.type,"duration_sec",l.duration_sec,"is_previewable",l.is_previewable))
           FROM lessons l WHERE l.section_id=s.id ORDER BY l.sort_order) AS lessons
          FROM sections s WHERE s.course_id=? ORDER BY s.sort_order');
        $secs->execute([$course['id']]);
        $course['sections'] = $secs->fetchAll();
        $this->json(['data'=>$course]);
    }

    // GET /api/v1/courses/:uuid/progress
    public function progress(array $params): void
    {
        $user = $this->apiAuth();
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id FROM courses WHERE uuid=? LIMIT 1');
        $stmt->execute([$params['uuid']??'']);
        $course = $stmt->fetch();
        if (!$course) { http_response_code(404); $this->json(['error'=>'Course not found.']); }

        $model = new Enrollment();
        $enroll = $model->findEnrollment((int)$course['id'], (int)$user['id']);
        if (!$enroll) { http_response_code(404); $this->json(['error'=>'Not enrolled.']); }

        $prog = $pdo->prepare('SELECT l.id,l.title,lp.status,lp.progress_pct FROM lessons l LEFT JOIN lesson_progress lp ON lp.lesson_id=l.id AND lp.enrollment_id=? WHERE l.course_id=? ORDER BY l.sort_order');
        $prog->execute([$enroll['id'],$course['id']]);
        $this->json(['enrollment'=>$enroll,'lessons'=>$prog->fetchAll()]);
    }
}
