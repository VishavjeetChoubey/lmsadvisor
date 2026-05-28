<?php
declare(strict_types=1);
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Database;

class SsoController extends Controller
{
    public function handle(array $p): void
    {
        $token = $this->request->get('token', '');
        if (!$token) { $this->redirect('/login'); }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT st.*, u.id AS uid, u.uuid AS user_uuid, u.email, u.first_name, u.last_name,
                    r.name AS role
             FROM sso_tokens st
             JOIN users u ON u.id=st.user_id
             JOIN roles r ON r.id=u.role_id
             WHERE st.token=? AND st.used=0 AND st.expires_at>NOW() LIMIT 1'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->flash('error', 'This login link has expired. Please purchase the course again or contact support.');
            $this->redirect('/login');
        }

        // Mark token used
        $pdo->prepare('UPDATE sso_tokens SET used=1 WHERE token=?')->execute([$token]);

        // Start session
        session_regenerate_id(true);
        $_SESSION['user_id']    = $row['uid'];
        $_SESSION['user_uuid']  = $row['user_uuid'];
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['user_name']  = $row['first_name'] . ' ' . $row['last_name'];
        $_SESSION['user_role']  = $row['role'];

        $path = $row['redirect_path'] ?? '/learn/dashboard';
        $this->redirect($path);
    }
}
