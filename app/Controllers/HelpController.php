<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Sanitizer;

/**
 * Public-facing Knowledge Base / Help Center.
 * Accessible without login.
 */
class HelpController extends Controller
{
    public function index(array $p): void
    {
        $pdo = Database::getInstance();

        // Categories with article counts
        $cats = $pdo->query(
            "SELECT c.*, COUNT(a.id) AS article_count
             FROM kb_categories c
             LEFT JOIN kb_articles a ON a.category_id=c.id AND a.status='published'
             GROUP BY c.id
             HAVING article_count > 0
             ORDER BY c.sort_order"
        )->fetchAll();

        // Popular articles (most viewed)
        $popular = $pdo->query(
            "SELECT a.uuid, a.title, a.slug, a.views, c.name AS cat_name
             FROM kb_articles a
             LEFT JOIN kb_categories c ON c.id=a.category_id
             WHERE a.status='published'
             ORDER BY a.views DESC LIMIT 8"
        )->fetchAll();

        $this->view('help.index', [
            'title'      => 'Help Center',
            'categories' => $cats,
            'popular'    => $popular,
        ], 'help');
    }

    public function category(array $p): void
    {
        $pdo  = Database::getInstance();
        $slug = Sanitizer::string($p['slug'] ?? '', 120);

        $cat = $pdo->prepare("SELECT * FROM kb_categories WHERE slug=? LIMIT 1");
        $cat->execute([$slug]);
        $cat = $cat->fetch();
        if (!$cat) { $this->redirect('/help'); }

        $articles = $pdo->prepare(
            "SELECT a.uuid, a.title, a.slug, a.views, a.updated_at
             FROM kb_articles a
             WHERE a.category_id=? AND a.status='published'
             ORDER BY a.title"
        );
        $articles->execute([(int)$cat['id']]);

        $this->view('help.category', [
            'title'    => $cat['name'] . ' — Help Center',
            'cat'      => $cat,
            'articles' => $articles->fetchAll(),
        ], 'help');
    }

    public function article(array $p): void
    {
        $pdo  = Database::getInstance();
        $slug = Sanitizer::string($p['slug'] ?? '', 255);

        $stmt = $pdo->prepare(
            "SELECT a.*, c.name AS cat_name, c.slug AS cat_slug
             FROM kb_articles a
             LEFT JOIN kb_categories c ON c.id=a.category_id
             WHERE a.slug=? AND a.status='published' LIMIT 1"
        );
        $stmt->execute([$slug]);
        $article = $stmt->fetch();
        if (!$article) { $this->redirect('/help'); }

        // Increment views
        $pdo->prepare('UPDATE kb_articles SET views=views+1 WHERE id=?')
            ->execute([(int)$article['id']]);

        // Related articles (same category)
        $related = $pdo->prepare(
            "SELECT uuid, title, slug FROM kb_articles
             WHERE category_id=? AND status='published' AND id!=?
             ORDER BY views DESC LIMIT 4"
        );
        $related->execute([(int)$article['category_id'], (int)$article['id']]);

        $this->view('help.article', [
            'title'   => $article['title'] . ' — Help Center',
            'article' => $article,
            'related' => $related->fetchAll(),
        ], 'help');
    }

    public function search(array $p): void
    {
        $pdo = Database::getInstance();
        $q   = Sanitizer::string($this->request->get('q', ''), 120);

        $results = [];
        if ($q) {
            $like = '%' . $q . '%';
            $stmt = $pdo->prepare(
                "SELECT a.uuid, a.title, a.slug, a.views,
                        c.name AS cat_name, c.slug AS cat_slug,
                        SUBSTRING(a.body, 1, 200) AS excerpt
                 FROM kb_articles a
                 LEFT JOIN kb_categories c ON c.id=a.category_id
                 WHERE a.status='published' AND (a.title LIKE ? OR a.body LIKE ?)
                 ORDER BY a.views DESC LIMIT 20"
            );
            $stmt->execute([$like, $like]);
            $results = $stmt->fetchAll();
        }

        $this->view('help.search', [
            'title'   => $q ? "Search: {$q} — Help Center" : 'Search — Help Center',
            'q'       => $q,
            'results' => $results,
        ], 'help');
    }
}
