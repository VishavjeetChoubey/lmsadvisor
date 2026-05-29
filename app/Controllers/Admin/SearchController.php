<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Helpers\Sanitizer;

class SearchController extends Controller
{
    public function search(array $p): void
    {
        AuthMiddleware::handle();
        $q    = Sanitizer::string($this->request->get('q', ''), 120);
        $type = $this->request->get('type', 'all');

        if (!$q) {
            $this->json(['results' => [], 'total' => 0]);
        }

        $pdo     = Database::getInstance();
        $results = [];
        $like    = '%' . $q . '%';

        // Courses
        if ($type === 'all' || $type === 'courses') {
            $stmt = $pdo->prepare(
                'SELECT "course" AS type, uuid, title,
                        CONCAT(status, " · ", COALESCE(level,""), " · ",
                               (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id), " students") AS meta
                 FROM courses c WHERE title LIKE ? OR short_description LIKE ?
                 ORDER BY status="published" DESC LIMIT 8'
            );
            $stmt->execute([$like, $like]);
            $results = array_merge($results, $stmt->fetchAll());
        }

        // Students
        if ($type === 'all' || $type === 'students') {
            $stmt = $pdo->prepare(
                'SELECT "user" AS type, uuid, CONCAT(first_name," ",last_name) AS title,
                        CONCAT(email, " · ", (SELECT COUNT(*) FROM enrollments e WHERE e.user_id=u.id), " courses") AS meta
                 FROM users u WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?
                 ORDER BY created_at DESC LIMIT 8'
            );
            $stmt->execute([$like, $like, $like]);
            $results = array_merge($results, $stmt->fetchAll());
        }

        // Enrollments
        if ($type === 'all' || $type === 'enrollments') {
            $stmt = $pdo->prepare(
                'SELECT "enrollment" AS type, c.uuid, CONCAT(u.first_name," ",u.last_name) AS title,
                        CONCAT(c.title, " · ", e.status) AS meta
                 FROM enrollments e
                 JOIN users u ON u.id=e.user_id
                 JOIN courses c ON c.id=e.course_id
                 WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.title LIKE ?
                 ORDER BY e.enrolled_at DESC LIMIT 6'
            );
            $stmt->execute([$like, $like, $like, $like]);
            $results = array_merge($results, $stmt->fetchAll());
        }

        // Lessons
        if ($type === 'all' || $type === 'lessons') {
            $stmt = $pdo->prepare(
                'SELECT "lesson" AS type, c.uuid, l.title,
                        CONCAT(c.title, " · Section ", s.sort_order) AS meta
                 FROM lessons l
                 JOIN sections s ON s.id=l.section_id
                 JOIN courses c ON c.id=l.course_id
                 WHERE l.title LIKE ?
                 ORDER BY l.sort_order LIMIT 6'
            );
            $stmt->execute([$like]);
            $results = array_merge($results, $stmt->fetchAll());
        }

        $this->json(['results' => $results, 'total' => count($results), 'q' => $q]);
    }

    // Full search page
    public function page(array $p): void
    {
        AuthMiddleware::handle();
        $q = Sanitizer::string($this->request->get('q', ''), 120);
        $this->view('admin.search.results', [
            'title'     => $q ? "Search: {$q}" : 'Search',
            'q'         => $q,
            'auth_user' => \App\Services\AuthService::user(),
            'flash'     => $this->getFlash(),
        ]);
    }
}
