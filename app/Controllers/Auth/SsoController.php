<?php
declare(strict_types=1);
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Database;

/**
 * SsoController — Consumes a single-use SSO token and logs the student in.
 * GET /sso/login?token=XXXXXX
 */
class SsoController extends Controller
{
    public function handle(array $p): void
    {
        $token = trim((string)$this->request->get('token', ''));

        if (!$token) {
            $this->flash('error', 'No login token provided.');
            $this->redirect('/login');
        }

        $pdo = Database::getInstance();

        // Ensure table exists (in case migration hasn't run yet)
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS sso_tokens (
                    id            INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
                    user_id       INT UNSIGNED    NOT NULL,
                    token         VARCHAR(64)     NOT NULL UNIQUE,
                    redirect_path VARCHAR(300)    NOT NULL DEFAULT \'/learn/dashboard\',
                    expires_at    TIMESTAMP       NOT NULL,
                    used          TINYINT(1)      NOT NULL DEFAULT 0,
                    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_token (token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (\PDOException $e) {}

        // Look up token — NOT checking used=0 yet, check separately for better error msg
        $stmt = $pdo->prepare(
            'SELECT st.*, u.id AS uid, u.uuid AS user_uuid, u.email,
                    u.first_name, u.last_name, r.name AS role
             FROM sso_tokens st
             JOIN users u ON u.id = st.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE st.token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->flash('error', 'Invalid login link. Please go back and click Start Learning again.');
            $this->redirect('/login');
        }

        if ($row['used']) {
            $this->flash('error', 'This login link has already been used. Please click Start Learning again to get a new link.');
            $this->redirect('/login');
        }

        if (strtotime($row['expires_at']) < time()) {
            $this->flash('error', 'This login link has expired. Please click Start Learning again.');
            $this->redirect('/login');
        }

        // ── Mark used ─────────────────────────────────────────────────────────
        $pdo->prepare('UPDATE sso_tokens SET used = 1 WHERE token = ?')
            ->execute([$token]);

        // ── Log in the student ────────────────────────────────────────────────
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user_id']    = (int)$row['uid'];
        $_SESSION['user_uuid']  = $row['user_uuid'];
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['user_name']  = trim($row['first_name'] . ' ' . $row['last_name']);
        $_SESSION['user_role']  = $row['role'];
        $_SESSION['logged_in_at'] = time();

        // ── Redirect to course ────────────────────────────────────────────────
        $path = '/' . ltrim($row['redirect_path'] ?? '/learn/dashboard', '/');
        $this->redirect($path);
    }
}
