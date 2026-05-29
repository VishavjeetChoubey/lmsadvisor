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
        $uid  = (int)$user['id'];
        $pdo  = Database::getInstance();

        // Load full user data including role name
        $stmt = $pdo->prepare(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id=? LIMIT 1'
        );
        $stmt->execute([$uid]);
        $full = $stmt->fetch();

        // Recent activity
        $activity = $pdo->prepare(
            'SELECT l.title AS lesson_title, c.title AS course_title, lp.last_accessed, lp.status
             FROM lesson_progress lp
             JOIN lessons l ON l.id=lp.lesson_id
             JOIN courses c ON c.id=l.course_id
             WHERE lp.user_id=? ORDER BY lp.last_accessed DESC LIMIT 5'
        );
        $activity->execute([$uid]);

        // Stats — clean prepared statements
        $s1 = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id=?");
        $s1->execute([$uid]);
        $s2 = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id=? AND status='completed'");
        $s2->execute([$uid]);
        $s3 = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE user_id=?");
        $s3->execute([$uid]);

        $stats = [
            'enrollments'  => (int)$s1->fetchColumn(),
            'completions'  => (int)$s2->fetchColumn(),
            'certificates' => (int)$s3->fetchColumn(),
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
        $uid  = (int)$user['id']; // FIX: was missing, caused undefined variable on photo delete
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
                // Delete old photo if one exists
                $old = $pdo->prepare('SELECT profile_photo FROM users WHERE id=? LIMIT 1');
                $old->execute([$uid]);
                $oldPhoto = $old->fetchColumn();
                if ($oldPhoto) {
                    $oldFile = STORE_PATH . '/uploads/' . $oldPhoto;
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
        $params[] = $uid;

        try {
            $pdo->prepare($sql)->execute($params);
        } catch (\Throwable $e) {
            error_log('[Profile Update] ' . $e->getMessage());
            $this->flash('error', 'Could not save profile: ' . $e->getMessage());
            $this->redirect('/learn/profile');
        }

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
            $errMap = [
                UPLOAD_ERR_INI_SIZE  => 'File exceeds server upload limit.',
                UPLOAD_ERR_FORM_SIZE => 'File too large.',
                UPLOAD_ERR_PARTIAL   => 'Upload was interrupted.',
                UPLOAD_ERR_NO_FILE   => 'No file selected.',
            ];
            return ['success'=>false,'error'=>$errMap[$file['error']] ?? 'Upload failed.'];
        }

        // Validate MIME from actual file content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed, true)) {
            return ['success'=>false,'error'=>'Only JPG, PNG, GIF and WebP images are allowed.'];
        }
        if ($file['size'] > $maxBytes) {
            return ['success'=>false,'error'=>'Image must be under 2MB.'];
        }

        $ext  = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        // Use STORE_PATH — same as existing avatar upload in DashboardController
        // Files go to: BASE_PATH/storage/uploads/avatars/
        // Served at:   APP_URL/storage/uploads/avatars/
        $userId   = \App\Services\AuthService::user()['id'] ?? 0;
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $dir      = STORE_PATH . '/uploads/avatars/';

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['success'=>false,'error'=>'Server error: could not create upload directory.'];
        }

        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            return ['success'=>false,'error'=>'Could not save image. Check server file permissions.'];
        }

        // Store relative path — rendered as: APP_URL/storage/uploads/ . this value
        return ['success'=>true,'path'=>'avatars/' . $filename];
    }
}
