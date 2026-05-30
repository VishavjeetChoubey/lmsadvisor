<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\OrganisationService;
use App\Services\AuthService;
use App\Helpers\Sanitizer;

class OrganisationController extends Controller
{
    private function guard(): void { AuthMiddleware::handle(); }

    public function index(array $p): void
    {
        $this->guard();
        $this->view('admin.organisations.index', [
            'title'   => 'Organisations',
            'orgs'    => OrganisationService::all(),
            'flash'   => $this->getFlash(),
            'auth_user'=> AuthService::user(),
        ]);
    }

    public function show(array $p): void
    {
        $this->guard();
        $org = OrganisationService::findByUuid($p['uuid'] ?? '');
        if (!$org) { $this->flash('error','Not found.'); $this->redirect('/admin/organisations'); }
        $members = OrganisationService::members((int)$org['id']);
        $report  = OrganisationService::complianceReport((int)$org['id']);

        // Available courses for assignment
        $pdo      = \App\Core\Database::getInstance();
        $courses  = $pdo->query("SELECT id, uuid, title FROM courses WHERE status='published' ORDER BY title")->fetchAll();
        $assigned = $pdo->prepare('SELECT course_id FROM course_assignments WHERE organisation_id=?');
        $assigned->execute([(int)$org['id']]);
        $assignedIds = $assigned->fetchAll(\PDO::FETCH_COLUMN);

        $this->view('admin.organisations.show', [
            'title'      => $org['name'] . ' — Organisation',
            'org'        => $org,
            'members'    => $members,
            'report'     => $report,
            'courses'    => $courses,
            'assignedIds'=> $assignedIds,
            'flash'      => $this->getFlash(),
            'csrf_token' => CsrfMiddleware::token(),
            'auth_user'  => AuthService::user(),
        ]);
    }

    public function store(array $p): void
    {
        $this->guard(); CsrfMiddleware::verify();
        $uuid = OrganisationService::create([
            'name'          => Sanitizer::string($this->request->post('name',''), 120),
            'domain'        => Sanitizer::string($this->request->post('domain',''), 255),
            'seat_limit'    => (int)$this->request->post('seat_limit', 50),
            'billing_email' => Sanitizer::email($this->request->post('billing_email','')),
        ]);
        $this->flash('success','Organisation created.');
        $this->redirect('/admin/organisations/' . $uuid);
    }

    public function assignCourse(array $p): void
    {
        $this->guard(); CsrfMiddleware::verify();
        $org      = OrganisationService::findByUuid($p['uuid'] ?? '');
        if (!$org) { $this->json(['success'=>false,'message'=>'Not found'], 404); }
        $courseId = (int)$this->request->post('course_id', 0);
        $user     = AuthService::user();
        OrganisationService::assignCourse(
            (int)$org['id'], $courseId, (int)$user['id'],
            $this->request->post('due_date','') ?: null,
            (bool)$this->request->post('mandatory', true)
        );
        $this->json(['success'=>true,'message'=>'Course assigned and members enrolled.']);
    }

    public function exportReport(array $p): void
    {
        $this->guard();
        $org    = OrganisationService::findByUuid($p['uuid'] ?? '');
        if (!$org) { http_response_code(404); exit; }
        $report = OrganisationService::complianceReport((int)$org['id']);

        // CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="compliance-' . $org['uuid'] . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Name','Email','Department','Role','Course','Status','Completed At','Due Date','Overdue']);
        foreach ($report as $member) {
            foreach ($member['courses'] as $c) {
                fputcsv($out, [
                    $member['name'], $member['email'], $member['department'], $member['role'],
                    $c['course_title'], $c['status'], $c['completed_at'] ?? '',
                    $c['due_date'] ?? '', $c['overdue'] ? 'YES' : 'NO',
                ]);
            }
        }
        fclose($out);
        exit;
    }
}

    public function addMember(array $p): void
    {
        AuthMiddleware::handle();
        \App\Middleware\CsrfMiddleware::verify();
        $org    = OrganisationService::findByUuid($p['uuid'] ?? '');
        if (!$org) { $this->json(['success'=>false,'message'=>'Organisation not found.']); }

        $userId = (int)$this->request->post('user_id', 0);
        $role   = in_array($this->request->post('role'), ['employee','manager']) ? $this->request->post('role') : 'employee';
        $dept   = \App\Helpers\Sanitizer::string($this->request->post('department',''), 120);

        if (!$userId) { $this->json(['success'=>false,'message'=>'Please select a user.']); }

        try {
            OrganisationService::addMember((int)$org['id'], $userId, $role, $dept);
            $this->json(['success'=>true,'message'=>'Member added successfully.']);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }
