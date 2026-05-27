<?php
declare(strict_types=1);
namespace App\Controllers\Student;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\LearningPathService;
use App\Core\Database;

class PathController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
    }

    public function index(array $p): void
    {
        $user  = AuthService::user();
        $paths = LearningPathService::all((int)$user['id']);

        $this->view('student.paths.index', [
            'title'       => 'Learning Paths',
            'page_title'  => 'Learning Paths',
            'auth_user'   => $user,
            'flash'       => $this->getFlash(),
            'paths'       => $paths,
        ], 'student');
    }

    public function detail(array $p): void
    {
        $user  = AuthService::user();
        $pdo   = Database::getInstance();
        $stmt  = $pdo->prepare('SELECT * FROM learning_paths WHERE uuid=? AND is_published=1 LIMIT 1');
        $stmt->execute([$p['uuid'] ?? '']);
        $lp    = $stmt->fetch();
        if (!$lp) { $this->flash('error','Path not found.'); $this->redirect('/learn/paths'); }

        $path  = LearningPathService::findWithCourses($lp['id'], (int)$user['id']);
        $pct   = LearningPathService::progressPct($lp['id'], (int)$user['id']);

        $this->view('student.paths.detail', [
            'title'      => $path['title'] . ' — Learning Path',
            'page_title' => $path['title'],
            'auth_user'  => $user,
            'flash'      => $this->getFlash(),
            'path'       => $path,
            'pct'        => $pct,
            'csrf_token' => CsrfMiddleware::token(),
        ], 'student');
    }

    public function enroll(array $p): void
    {
        CsrfMiddleware::verify();
        $user = AuthService::user();
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM learning_paths WHERE uuid=? LIMIT 1');
        $stmt->execute([$p['uuid'] ?? '']);
        $lp   = $stmt->fetch();
        if (!$lp) { $this->json(['success'=>false,'message'=>'Path not found.']); }

        LearningPathService::enroll($lp['id'], (int)$user['id']);
        $this->json(['success'=>true,'message'=>'Enrolled in all courses in this path!']);
    }
}
