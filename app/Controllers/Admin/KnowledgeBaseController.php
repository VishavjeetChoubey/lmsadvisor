<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Helpers\Sanitizer;
use App\Helpers\Slug;
use App\Helpers\Uuid;
use App\Models\AuditLog;

class KnowledgeBaseController extends Controller
{
    private \PDO $pdo;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
        $this->pdo = Database::getInstance();
    }

    // ── GET /admin/knowledge-base ─────────────────────────────────────────────
    public function index(array $params): void
    {
        $page   = max(1, (int)$this->request->get('page', 1));
        $search = Sanitizer::string($this->request->get('search', ''), 100);
        $catId  = (int)$this->request->get('category', 0);
        $perPage= 20;

        $where  = ['1=1'];
        $binds  = [];
        if ($search) { $where[] = '(a.title LIKE ? OR a.body LIKE ?)'; $binds[] = "%$search%"; $binds[] = "%$search%"; }
        if ($catId)  { $where[] = 'a.category_id = ?'; $binds[] = $catId; }
        $whereStr = implode(' AND ', $where);

        $total = (int)$this->pdo->prepare("SELECT COUNT(*) FROM kb_articles a WHERE $whereStr")->execute($binds)
               ? ($stmt = $this->pdo->prepare("SELECT COUNT(*) FROM kb_articles a WHERE $whereStr") and $stmt->execute($binds) and (int)$stmt->fetchColumn())
               : 0;
        $totalStmt = $this->pdo->prepare("SELECT COUNT(*) FROM kb_articles a WHERE $whereStr");
        $totalStmt->execute($binds);
        $total = (int)$totalStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt   = $this->pdo->prepare(
            "SELECT a.*, c.name AS cat_name, u.first_name, u.last_name
             FROM kb_articles a
             LEFT JOIN kb_categories c ON c.id = a.category_id
             LEFT JOIN users u ON u.id = a.created_by
             WHERE $whereStr
             ORDER BY a.updated_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($binds);
        $articles = $stmt->fetchAll();

        $cats = $this->pdo->query('SELECT * FROM kb_categories ORDER BY name')->fetchAll();

        $this->view('admin.knowledge_base.index', [
            'title'       => 'Knowledge Base — LMSAdvisor',
            'page_title'  => 'Knowledge Base',
            'breadcrumbs' => [['label' => 'Knowledge Base']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'articles'    => $articles,
            'categories'  => $cats,
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => (int)ceil($total / $perPage),
            'search'      => $search,
            'catFilter'   => $catId,
        ]);
    }

    // ── GET /admin/knowledge-base/create ─────────────────────────────────────
    public function create(array $params): void
    {
        $cats = $this->pdo->query('SELECT * FROM kb_categories ORDER BY name')->fetchAll();
        $this->view('admin.knowledge_base.form', [
            'title'       => 'New Article — Knowledge Base',
            'page_title'  => 'New KB Article',
            'breadcrumbs' => [['label'=>'Knowledge Base','url'=>'admin/knowledge-base'],['label'=>'New Article']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'article'     => [],
            'categories'  => $cats,
        ]);
    }

    // ── POST /admin/knowledge-base/create ─────────────────────────────────────
    public function store(array $params): void
    {
        CsrfMiddleware::verify();
        $user   = AuthService::user();
        $title  = Sanitizer::string($this->request->post('title', ''), 255);
        $slug   = Slug::make($title);
        $body   = $this->request->post('body', '');
        $catId  = (int)$this->request->post('category_id', 0) ?: null;
        $status = $this->request->post('status', 'draft');
        $uuid   = Uuid::v4();

        // Unique slug
        $existing = $this->pdo->prepare('SELECT id FROM kb_articles WHERE slug=? LIMIT 1');
        $existing->execute([$slug]);
        if ($existing->fetch()) $slug .= '-' . substr($uuid, 0, 6);

        $this->pdo->prepare(
            'INSERT INTO kb_articles (uuid,category_id,title,slug,body,status,created_by)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([$uuid, $catId, $title, $slug, $body, $status, $user['id']]);

        $id = $this->pdo->lastInsertId();
        AuditLog::write('kb.create', 'kb_article', (int)$id, null, ['title'=>$title]);
        $this->flash('success', 'Article created.');
        $this->redirect('/admin/knowledge-base');
    }

    // ── GET /admin/knowledge-base/:uuid/edit ─────────────────────────────────
    public function edit(array $params): void
    {
        $article = $this->findOrFail($params['uuid'] ?? '');
        $cats    = $this->pdo->query('SELECT * FROM kb_categories ORDER BY name')->fetchAll();
        $this->view('admin.knowledge_base.form', [
            'title'       => 'Edit: ' . $article['title'],
            'page_title'  => 'Edit KB Article',
            'breadcrumbs' => [['label'=>'Knowledge Base','url'=>'admin/knowledge-base'],['label'=>'Edit']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'article'     => $article,
            'categories'  => $cats,
        ]);
    }

    // ── POST /admin/knowledge-base/:uuid/edit ────────────────────────────────
    public function update(array $params): void
    {
        CsrfMiddleware::verify();
        $article = $this->findOrFail($params['uuid'] ?? '');
        $title   = Sanitizer::string($this->request->post('title', ''), 255);
        $body    = $this->request->post('body', '');
        $catId   = (int)$this->request->post('category_id', 0) ?: null;
        $status  = $this->request->post('status', 'draft');

        $this->pdo->prepare(
            'UPDATE kb_articles SET title=?,body=?,category_id=?,status=?,updated_at=NOW() WHERE id=?'
        )->execute([$title, $body, $catId, $status, $article['id']]);

        AuditLog::write('kb.update', 'kb_article', (int)$article['id']);
        $this->flash('success', 'Article updated.');
        $this->redirect('/admin/knowledge-base');
    }

    // ── POST /admin/knowledge-base/:uuid/delete ──────────────────────────────
    public function delete(array $params): void
    {
        CsrfMiddleware::verify();
        $article = $this->findOrFail($params['uuid'] ?? '');
        $this->pdo->prepare('DELETE FROM kb_articles WHERE id=?')->execute([$article['id']]);
        AuditLog::write('kb.delete', 'kb_article', (int)$article['id'], ['title'=>$article['title']]);
        $this->json(['success' => true]);
    }

    // ── GET/POST /admin/knowledge-base/categories ────────────────────────────
    public function categories(array $params): void
    {
        if ($this->request->method() === 'POST') {
            CsrfMiddleware::verify();
            $name = Sanitizer::string($this->request->post('name', ''), 120);
            $slug = Slug::make($name);
            $this->pdo->prepare('INSERT INTO kb_categories (name,slug) VALUES (?,?)')->execute([$name, $slug]);
            $this->json(['success' => true, 'id' => $this->pdo->lastInsertId(), 'name' => $name]);
            return;
        }
        $cats = $this->pdo->query('SELECT * FROM kb_categories ORDER BY name')->fetchAll();
        $this->json(['success' => true, 'categories' => $cats]);
    }

    private function findOrFail(string $uuid): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM kb_articles WHERE uuid=? LIMIT 1');
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        if (!$row) { $this->flash('error', 'Article not found.'); $this->redirect('/admin/knowledge-base'); }
        return $row;
    }
}
