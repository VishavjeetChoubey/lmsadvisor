<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\LearningPathService;
use App\Core\Database;
use App\Helpers\Sanitizer;

class LearningPathController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin','admin','manager']);
    }

    public function index(array $p): void
    {
        $paths = LearningPathService::all();
        $this->view('admin.learning_paths.index', [
            'title'       => 'Learning Paths',
            'page_title'  => 'Learning Paths',
            'breadcrumbs' => [['label'=>'Learning Paths']],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'flash'       => $this->getFlash(),
            'paths'       => $paths,
        ]);
    }

    public function create(array $p): void
    {
        $pdo     = Database::getInstance();
        $courses = $pdo->query('SELECT id,uuid,title FROM courses WHERE status=\'published\' ORDER BY title')->fetchAll();
        $this->view('admin.learning_paths.form', [
            'title'       => 'New Learning Path',
            'page_title'  => 'New Learning Path',
            'breadcrumbs' => [['label'=>'Learning Paths','url'=>'admin/learning-paths'],['label'=>'New']],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'path'        => null,
            'courses'     => $courses,
            'path_courses'=> [],
        ]);
    }

    public function store(array $p): void
    {
        CsrfMiddleware::verify();
        $user   = AuthService::user();
        $pathId = LearningPathService::create([
            'title'        => Sanitizer::string($this->request->post('title',''),255),
            'description'  => $this->request->post('description',''),
            'is_published' => $this->request->post('is_published','0'),
            'created_by'   => $user['id'],
        ]);
        $courseIds = $this->request->post('course_ids',[]);
        if (is_array($courseIds)) LearningPathService::syncCourses($pathId, $courseIds);

        $this->flash('success','Learning path created.');
        $this->redirect('/admin/learning-paths');
    }

    public function edit(array $p): void
    {
        $pdo     = Database::getInstance();
        $stmt    = $pdo->prepare('SELECT * FROM learning_paths WHERE uuid=? LIMIT 1');
        $stmt->execute([$p['uuid'] ?? '']);
        $path    = $stmt->fetch();
        if (!$path) { $this->flash('error','Not found.'); $this->redirect('/admin/learning-paths'); }

        $courses     = $pdo->query('SELECT id,uuid,title FROM courses WHERE status=\'published\' ORDER BY title')->fetchAll();
        $stmt        = $pdo->prepare('SELECT course_id FROM learning_path_courses WHERE path_id=? ORDER BY sort_order');
        $stmt->execute([$path['id']]);
        $pathCourses = array_column($stmt->fetchAll(), 'course_id');

        $this->view('admin.learning_paths.form', [
            'title'       => 'Edit Path',
            'page_title'  => 'Edit Learning Path',
            'breadcrumbs' => [['label'=>'Learning Paths','url'=>'admin/learning-paths'],['label'=>$path['title']]],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'path'        => $path,
            'courses'     => $courses,
            'path_courses'=> $pathCourses,
        ]);
    }

    public function update(array $p): void
    {
        CsrfMiddleware::verify();
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM learning_paths WHERE uuid=? LIMIT 1');
        $stmt->execute([$p['uuid'] ?? '']);
        $path = $stmt->fetch();
        if (!$path) { $this->redirect('/admin/learning-paths'); }

        $pdo->prepare(
            'UPDATE learning_paths SET title=?, description=?, is_published=? WHERE id=?'
        )->execute([
            Sanitizer::string($this->request->post('title',''),255),
            $this->request->post('description',''),
            $this->request->post('is_published','0') ? 1 : 0,
            $path['id'],
        ]);
        $courseIds = $this->request->post('course_ids',[]);
        if (is_array($courseIds)) LearningPathService::syncCourses($path['id'], $courseIds);

        $this->flash('success','Path updated.');
        $this->redirect('/admin/learning-paths');
    }

    public function delete(array $p): void
    {
        CsrfMiddleware::verify();
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id FROM learning_paths WHERE uuid=?');
        $stmt->execute([$p['uuid'] ?? '']);
        $row  = $stmt->fetch();
        if ($row) $pdo->prepare('DELETE FROM learning_paths WHERE id=?')->execute([$row['id']]);
        $this->flash('success','Path deleted.');
        $this->redirect('/admin/learning-paths');
    }
}
