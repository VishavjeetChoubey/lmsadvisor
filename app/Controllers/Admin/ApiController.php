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
use App\Models\AuditLog;

class ApiController extends Controller
{
    private \PDO $pdo;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin']);
        $this->pdo = Database::getInstance();
    }

    // ── GET /admin/api ────────────────────────────────────────────────────────
    public function index(array $params): void
    {
        $tokens = $this->pdo->query(
            'SELECT t.*, u.first_name, u.last_name, u.email
             FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             ORDER BY t.created_at DESC'
        )->fetchAll();

        // Stats
        $totalRequests = array_sum(array_column($tokens, 'request_count'));
        $activeTokens  = count(array_filter($tokens, fn($t) => $t['is_active']));
        $recentEvents  = $this->pdo->query(
            'SELECT * FROM security_events ORDER BY created_at DESC LIMIT 10'
        )->fetchAll();

        $auditLogs = $this->pdo->query(
            "SELECT al.*, u.first_name, u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.action LIKE 'api.%'
             ORDER BY al.created_at DESC LIMIT 20"
        )->fetchAll();

        $this->view('admin.api.index', [
            'title'         => 'API Management — LMSAdvisor',
            'page_title'    => 'API Management',
            'breadcrumbs'   => [['label' => 'API Management']],
            'flash'         => $this->getFlash(),
            'auth_user'     => AuthService::user(),
            'csrf_token'    => CsrfMiddleware::token(),
            'tokens'        => $tokens,
            'total_requests'=> $totalRequests,
            'active_tokens' => $activeTokens,
            'recent_events' => $recentEvents,
            'audit_logs'    => $auditLogs,
        ]);
    }

    // ── POST /admin/api/tokens/create ─────────────────────────────────────────
    public function createToken(array $params): void
    {
        CsrfMiddleware::verify();
        $user    = AuthService::user();
        $name    = Sanitizer::string($this->request->post('name', ''), 120);
        $scopes  = $this->request->post('scopes', []);
        $userId  = (int)$this->request->post('user_id', $user['id']);
        $ipWhite = Sanitizer::string($this->request->post('ip_whitelist', ''), 500);
        $expDays = (int)$this->request->post('expires_days', 0);
        $desc    = Sanitizer::string($this->request->post('description', ''), 255);

        if (!$name) {
            $this->json(['success' => false, 'message' => 'Token name is required.']);
        }

        $token     = bin2hex(random_bytes(16)); // 32-char hex token fits CHAR(32)
        $scopeStr  = implode(',', array_map(fn($s) => Sanitizer::string($s, 30), (array)$scopes));
        $expiresAt = $expDays > 0 ? date('Y-m-d H:i:s', strtotime("+{$expDays} days")) : null;

        $this->pdo->prepare(
            'INSERT INTO api_tokens
             (user_id, token, name, description, scopes, ip_whitelist, expires_at, is_active, created_by)
             VALUES (?,?,?,?,?,?,?,1,?)'
        )->execute([$userId, $token, $name, $desc, $scopeStr, $ipWhite ?: null, $expiresAt, $user['id']]);

        AuditLog::write('api.token_created', 'api_token', (int)$this->pdo->lastInsertId(), null, [
            'name' => $name, 'scopes' => $scopeStr, 'user_id' => $userId,
        ]);

        $this->json(['success' => true, 'token' => $token, 'name' => $name]);
    }

    // ── POST /admin/api/tokens/:id/revoke ─────────────────────────────────────
    public function revokeToken(array $params): void
    {
        CsrfMiddleware::verify();
        $id = (int)($params['id'] ?? 0);
        $this->pdo->prepare('UPDATE api_tokens SET is_active=0 WHERE id=?')->execute([$id]);
        AuditLog::write('api.token_revoked', 'api_token', $id);
        $this->json(['success' => true]);
    }

    // ── POST /admin/api/tokens/:id/rotate ─────────────────────────────────────
    public function rotateToken(array $params): void
    {
        CsrfMiddleware::verify();
        $id       = (int)($params['id'] ?? 0);
        $newToken = bin2hex(random_bytes(16));
        $this->pdo->prepare('UPDATE api_tokens SET token=?, request_count=0 WHERE id=?')->execute([$newToken, $id]);
        AuditLog::write('api.token_rotated', 'api_token', $id);
        $this->json(['success' => true, 'token' => $newToken]);
    }

    // ── GET /admin/api/docs ───────────────────────────────────────────────────
    public function docs(array $params): void
    {
        $this->view('admin.api.docs', [
            'title'      => 'API Documentation — LMSAdvisor',
            'page_title' => 'API Documentation',
            'breadcrumbs'=> [['label'=>'API Management','url'=>'admin/api'],['label'=>'Docs']],
            'auth_user'  => AuthService::user(),
            'csrf_token' => CsrfMiddleware::token(),
            'base_url'   => APP_URL,
        ]);
    }
}
