<?php
declare(strict_types=1);
namespace App\Controllers\Student;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Helpers\Sanitizer;

class ProfileController extends Controller
{
    public function show(array $p): void
    {
        AuthMiddleware::handle();
        $user = AuthService::user();
        $pdo  = Database::getInstance();

        // Load full user data
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
        $stmt->execute([(int)$user['id']]);
        $full = $stmt->fetch();

        // Recent activity
        $activity = $pdo->prepare(
            'SELECT l.title AS lesson_title, c.title AS course_title, lp.last_accessed, lp.status
             FROM lesson_progress lp
             JOIN lessons l ON l.id=lp.lesson_id
             JOIN courses c ON c.id=l.course_id
             WHERE lp.user_id=? ORDER BY lp.last_accessed DESC LIMIT 5'
        );
        $activity->execute([(int)$user['id']]);

        // Stats
        $stats = [
            'enrollments' => (int)$pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE user_id=?')
                ->execute([(int)$user['id']]) ? (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id={$user['id']}")->fetchColumn() : 0,
            'completions' => (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id={$user['id']} AND status='completed'")->fetchColumn(),
            'certificates'=> (int)$pdo->query("SELECT COUNT(*) FROM certificates WHERE user_id={$user['id']}")->fetchColumn(),
        ];

        $this->view('student.profile.index', [
            'title'      => 'My Profile',
            'auth_user'  => $user,
            'full_user'  => $full,
            'activity'   => $activity->fetchAll(),
            'stats'      => $stats,
            'flash'      => $this->getFlash(),
            'csrf_token' => CsrfMiddleware::token(),
        ], 'student');
    }

    public function update(array $p): void
    {
        AuthMiddleware::handle();
        CsrfMiddleware::verify();
        $user = AuthService::user();
        $pdo  = Database::getInstance();

        $firstName = Sanitizer::string($this->request->post('first_name', ''), 80);
        $lastName  = Sanitizer::string($this->request->post('last_name', ''), 80);
        $bio       = Sanitizer::string($this->request->post('bio', ''), 1000);
        $timezone  = Sanitizer::string($this->request->post('timezone', 'UTC'), 60);
        $phone     = Sanitizer::string($this->request->post('phone', ''), 30);

        // Handle photo upload
        $photoPath = null;
        if (!empty($_FILES['profile_photo']['tmp_name'])) {
            $result = $this->handlePhotoUpload($_FILES['profile_photo']);
            if ($result['success']) {
                $photoPath = $result['path'];
                // Delete old photo
                $old = $pdo->prepare('SELECT profile_photo FROM users WHERE id=? LIMIT 1');
                $old->execute([(int)$user['id']]);
                $oldPhoto = $old->fetchColumn();
                if ($oldPhoto) {
                    $oldFile = BASE_PATH . '/public/storage/uploads/' . $oldPhoto;
                    if (file_exists($oldFile)) @unlink($oldFile);
                }
            } else {
                $this->flash('error', $result['error']);
                $this->redirect('/learn/profile');
            }
        }

        $sql    = 'UPDATE users SET first_name=?, last_name=?, bio=?, timezone=?, phone=?';
        $params = [$firstName, $lastName, $bio, $timezone, $phone];

        if ($photoPath) {
            $sql    .= ', profile_photo=?';
            $params[] = $photoPath;
        }
        $sql    .= ' WHERE id=?';
        $params[] = (int)$user['id'];
        $pdo->prepare($sql)->execute($params);

        $this->flash('success', 'Profile updated successfully.');
        $this->redirect('/learn/profile');
    }

    public function changePassword(array $p): void
    {
        AuthMiddleware::handle();
        CsrfMiddleware::verify();
        $user = AuthService::user();
        $pdo  = Database::getInstance();

        $current = $this->request->post('current_password', '');
        $new     = $this->request->post('new_password', '');
        $confirm = $this->request->post('confirm_password', '');

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
        $stmt->execute([(int)$user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $this->flash('error', 'Current password is incorrect.');
            $this->redirect('/learn/profile');
        }
        if (strlen($new) < 8) {
            $this->flash('error', 'New password must be at least 8 characters.');
            $this->redirect('/learn/profile');
        }
        if ($new !== $confirm) {
            $this->flash('error', 'Passwords do not match.');
            $this->redirect('/learn/profile');
        }

        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
            ->execute([password_hash($new, PASSWORD_BCRYPT), (int)$user['id']]);

        $this->flash('success', 'Password changed successfully.');
        $this->redirect('/learn/profile');
    }

    private function handlePhotoUpload(array $file): array
    {
        $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
        $maxBytes = 2 * 1024 * 1024; // 2MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success'=>false,'error'=>'Upload failed. Please try again.'];
        }
        if (!in_array($file['type'], $allowed, true)) {
            return ['success'=>false,'error'=>'Only JPG, PNG, GIF, and WebP images allowed.'];
        }
        if ($file['size'] > $maxBytes) {
            return ['success'=>false,'error'=>'Image must be under 2MB.'];
        }

        $ext    = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name   = 'avatars/' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
        $dir    = BASE_PATH . '/public/storage/uploads/avatars/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $dir . basename($name))) {
            return ['success'=>false,'error'=>'Could not save the image.'];
        }
        return ['success'=>true,'path'=>$name];
    }
}
