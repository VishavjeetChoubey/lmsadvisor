<?php
declare(strict_types=1);
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Database;

class UnsubscribeController extends Controller
{
    public function handle(array $params): void
    {
        $token = $params['token'] ?? '';
        $pdo   = Database::getInstance();
        $stmt  = $pdo->prepare('SELECT id, email FROM email_unsubscribes WHERE token=? LIMIT 1');
        $stmt->execute([$token]);
        $row   = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo '<html><body style="font-family:sans-serif;text-align:center;padding:60px">
                  <h2>Invalid unsubscribe link</h2>
                  <p>This link has expired or is invalid.</p></body></html>';
            return;
        }

        // Mark as unsubscribed (already in table — that IS the unsubscribe record)
        // The email_unsubscribes table already blocks future emails to this address
        echo '<html><body style="font-family:Inter,sans-serif;text-align:center;padding:60px;background:#f5f6fa">
              <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:16px;padding:40px;box-shadow:0 4px 24px rgba(0,0,0,.08)">
              <div style="font-size:48px;margin-bottom:16px">✅</div>
              <h2 style="color:#111827;margin-bottom:8px">Unsubscribed</h2>
              <p style="color:#6b7280"><strong>' . htmlspecialchars($row['email']) . '</strong> has been unsubscribed from all email notifications.</p>
              <p style="color:#6b7280;font-size:13px;margin-top:16px">You will still receive account security emails (password reset, login alerts).</p>
              </div></body></html>';
    }
}
