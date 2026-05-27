<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\GroupService;
use App\Core\Database;
use App\Helpers\Sanitizer;

class GroupController extends Controller
{
    public function __construct($req, $res) {
        parent::__construct($req, $res);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin','admin','manager']);
    }

    public function index(array $p): void
    {
        $this->view('admin.groups.index', [
            'title'       => 'Groups & Cohorts',
            'page_title'  => 'Groups & Cohorts',
            'breadcrumbs' => [['label'=>'Groups']],
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'flash'       => $this->getFlash(),
            'groups'      => GroupService::all(),
        ]);
    }

    public function create(array $p): void
    {
        $pdo   = Database::getInstance();
        $users = $pdo->query('SELECT id,first_name,last_name,email FROM users WHERE is_active=1 ORDER BY first_name')->fetchAll();
        $this->view('admin.groups.form', [
            'title'      => 'New Group',
            'page_title' => 'New Group',
            'breadcrumbs'=> [['label'=>'Groups','url'=>'admin/groups'],['label'=>'New']],
            'auth_user'  => AuthService::user(),
            'csrf_token' => CsrfMiddleware::token(),
            'group'      => null,
            'users'      => $users,
            'members'    => [],
        ]);
    }

    public function store(array $p): void
    {
        CsrfMiddleware::verify();
        $user    = AuthService::user();
        $groupId = GroupService::create([
            'name'        => Sanitizer::string($this->request->post('name',''),255),
            'description' => $this->request->post('description',''),
            'manager_id'  => $this->request->post('manager_id',''),
            'created_by'  => $user['id'],
        ]);
        // Add initial members
        foreach ((array)$this->request->post('user_ids',[]) as $uid) {
            GroupService::addMember($groupId, (int)$uid);
        }
        $this->flash('success','Group created.');
        $this->redirect('/admin/groups/' . $groupId . '/edit');
    }

    public function edit(array $p): void
    {
        $group = GroupService::find((int)($p['id'] ?? 0));
        if (!$group) { $this->flash('error','Group not found.'); $this->redirect('/admin/groups'); }
        $pdo     = Database::getInstance();
        $users   = $pdo->query('SELECT id,first_name,last_name,email FROM users WHERE is_active=1 ORDER BY first_name')->fetchAll();
        $courses = $pdo->query('SELECT id,uuid,title FROM courses WHERE status=\'published\' ORDER BY title')->fetchAll();

        $this->view('admin.groups.form', [
            'title'      => 'Edit Group',
            'page_title' => 'Edit Group',
            'breadcrumbs'=> [['label'=>'Groups','url'=>'admin/groups'],['label'=>$group['name']]],
            'auth_user'  => AuthService::user(),
            'csrf_token' => CsrfMiddleware::token(),
            'flash'      => $this->getFlash(),
            'group'      => $group,
            'users'      => $users,
            'courses'    => $courses,
            'members'    => GroupService::members($group['id']),
            'group_courses' => GroupService::courses($group['id']),
        ]);
    }

    public function update(array $p): void
    {
        CsrfMiddleware::verify();
        GroupService::update((int)$p['id'], [
            'name'        => Sanitizer::string($this->request->post('name',''),255),
            'description' => $this->request->post('description',''),
            'manager_id'  => $this->request->post('manager_id',''),
        ]);
        $this->flash('success','Group updated.');
        $this->redirect('/admin/groups/' . $p['id'] . '/edit');
    }

    public function addMember(array $p): void
    {
        CsrfMiddleware::verify();
        GroupService::addMember((int)$p['id'], (int)$this->request->post('user_id',0));
        // Enroll user in all group courses
        $courses = GroupService::courses((int)$p['id']);
        foreach ($courses as $c) {
            \App\Services\EnrollmentService::enroll((int)$c['id'], (int)$this->request->post('user_id',0));
        }
        $this->json(['success'=>true]);
    }

    public function removeMember(array $p): void
    {
        CsrfMiddleware::verify();
        GroupService::removeMember((int)$p['id'], (int)$this->request->post('user_id',0));
        $this->json(['success'=>true]);
    }

    public function assignCourse(array $p): void
    {
        CsrfMiddleware::verify();
        GroupService::assignCourse((int)$p['id'], (int)$this->request->post('course_id',0));
        $this->json(['success'=>true,'message'=>'Course assigned and all members enrolled.']);
    }

    public function removeCourse(array $p): void
    {
        CsrfMiddleware::verify();
        GroupService::removeCourse((int)$p['id'], (int)$this->request->post('course_id',0));
        $this->json(['success'=>true]);
    }

    public function report(array $p): void
    {
        $group  = GroupService::find((int)($p['id'] ?? 0));
        if (!$group) { $this->flash('error','Group not found.'); $this->redirect('/admin/groups'); }
        $report = GroupService::progressReport($group['id']);
        $this->view('admin.groups.report', [
            'title'      => 'Group Report — ' . $group['name'],
            'page_title' => 'Group Progress Report',
            'breadcrumbs'=> [['label'=>'Groups','url'=>'admin/groups'],['label'=>$group['name'],'url'=>'admin/groups/'.$group['id'].'/edit'],['label'=>'Report']],
            'auth_user'  => AuthService::user(),
            'csrf_token' => CsrfMiddleware::token(),
            'group'      => $group,
            'report'     => $report,
        ]);
    }

    public function delete(array $p): void
    {
        CsrfMiddleware::verify();
        GroupService::delete((int)$p['id']);
        $this->flash('success','Group deleted.');
        $this->redirect('/admin/groups');
    }
}
