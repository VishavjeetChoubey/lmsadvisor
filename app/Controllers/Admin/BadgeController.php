<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\GamificationService;
use App\Core\Database;

class BadgeController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin','admin']);
    }

    public function index(array $p): void
    {
        $pdo    = Database::getInstance();
        $badges = $pdo->query('SELECT b.*, COUNT(ub.user_id) AS earned_count
            FROM badges b LEFT JOIN user_badges ub ON ub.badge_id=b.id
            GROUP BY b.id ORDER BY b.rule_type, b.rule_value')->fetchAll();

        $this->view('admin.badges.index', [
            'title'       => 'Badges',
            'page_title'  => 'Gamification — Badges',
            'breadcrumbs' => [['label'=>'Badges']],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'flash'       => $this->getFlash(),
            'badges'      => $badges,
        ]);
    }

    public function store(array $p): void
    {
        CsrfMiddleware::verify();
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO badges (uuid,name,description,icon,color,rule_type,rule_value)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([
            \App\Helpers\Uuid::v4(),
            trim($this->request->post('name','')),
            trim($this->request->post('description','')),
            trim($this->request->post('icon','bi-award-fill')),
            trim($this->request->post('color','#5b5ef6')),
            $this->request->post('rule_type','manual'),
            (int)$this->request->post('rule_value',0),
        ]);
        $this->flash('success','Badge created.');
        $this->redirect('/admin/badges');
    }

    public function update(array $p): void
    {
        CsrfMiddleware::verify();
        $pdo = Database::getInstance();
        $pdo->prepare(
            'UPDATE badges SET name=?,description=?,icon=?,color=?,rule_type=?,rule_value=?,is_active=? WHERE id=?'
        )->execute([
            trim($this->request->post('name','')),
            trim($this->request->post('description','')),
            trim($this->request->post('icon','bi-award-fill')),
            trim($this->request->post('color','#5b5ef6')),
            $this->request->post('rule_type','manual'),
            (int)$this->request->post('rule_value',0),
            $this->request->post('is_active','0') ? 1 : 0,
            (int)$p['id'],
        ]);
        $this->flash('success','Badge updated.');
        $this->redirect('/admin/badges');
    }

    public function delete(array $p): void
    {
        CsrfMiddleware::verify();
        Database::getInstance()->prepare('DELETE FROM badges WHERE id=?')->execute([(int)$p['id']]);
        $this->json(['success'=>true]);
    }

    public function awardManual(array $p): void
    {
        CsrfMiddleware::verify();
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT IGNORE INTO user_badges (user_id,badge_id) VALUES (?,?)'
        )->execute([(int)$this->request->post('user_id',0),(int)$p['id']]);
        $this->json(['success'=>true]);
    }
}
