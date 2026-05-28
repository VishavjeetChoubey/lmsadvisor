<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;

/**
 * Admin Database Upgrader
 * Runs pending migrations, shows schema status, lets admin re-run specific migrations.
 */
class DatabaseController extends Controller
{
    public function index(array $params): void
    {
        AuthMiddleware::requireRole(['admin', 'super_admin']);

        $migrations = $this->getMigrationStatus();
        $dbVersion  = $this->getDbVersion();
        $tableStats = $this->getTableStats();

        $this->view('admin.database.index', [
            'title'      => 'Database Upgrader',
            'migrations' => $migrations,
            'dbVersion'  => $dbVersion,
            'tableStats' => $tableStats,
            'flash'      => $this->getFlash(),
        ]);
    }

    public function runAll(array $params): void
    {
        AuthMiddleware::requireRole(['admin', 'super_admin']);
        \App\Middleware\CsrfMiddleware::verify();

        $results = $this->runMigrations();
        $ran     = count(array_filter($results, fn($r) => $r['status'] === 'ran'));
        $skipped = count(array_filter($results, fn($r) => $r['status'] === 'skipped'));
        $errors  = count(array_filter($results, fn($r) => $r['status'] === 'error'));

        if ($errors === 0) {
            $this->flash('success', "Upgrade complete — {$ran} migration(s) ran, {$skipped} already up to date.");
        } else {
            $this->flash('error', "{$errors} migration(s) failed. {$ran} ran, {$skipped} skipped. Check details below.");
        }
        $this->redirect('/admin/database');
    }

    public function runOne(array $params): void
    {
        AuthMiddleware::requireRole(['admin', 'super_admin']);
        \App\Middleware\CsrfMiddleware::verify();

        $file    = basename($this->request->post('file', ''));
        $force   = (bool)$this->request->post('force', false);
        $result  = $this->runSingleMigration($file, $force);

        if ($result['status'] === 'ran') {
            $this->flash('success', "Migration {$file} ran successfully.");
        } elseif ($result['status'] === 'skipped') {
            $this->flash('info', "Migration {$file} already applied. Use 'Force Re-run' to run again.");
        } else {
            $this->flash('error', "Migration {$file} failed: " . $result['error']);
        }
        $this->redirect('/admin/database');
    }

    public function apiStatus(array $params): void
    {
        AuthMiddleware::requireRole(['admin', 'super_admin']);
        $this->json([
            'migrations' => $this->getMigrationStatus(),
            'version'    => $this->getDbVersion(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getMigrationDir(): string
    {
        return BASE_PATH . '/database/migrations';
    }

    private function ensureMigrationsTable(): void
    {
        $pdo = Database::getInstance();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                filename   VARCHAR(200) NOT NULL UNIQUE,
                applied_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                checksum   VARCHAR(64)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function getMigrationStatus(): array
    {
        $this->ensureMigrationsTable();
        $pdo     = Database::getInstance();
        $applied = $pdo->query('SELECT filename, applied_at FROM schema_migrations ORDER BY filename')
                       ->fetchAll(\PDO::FETCH_KEY_PAIR);

        $dir   = $this->getMigrationDir();
        $files = glob($dir . '/*.sql');
        sort($files);

        $result = [];
        foreach ($files as $path) {
            $file     = basename($path);
            $content  = file_get_contents($path);
            $checksum = md5($content);
            $result[] = [
                'file'       => $file,
                'applied'    => isset($applied[$file]),
                'applied_at' => $applied[$file] ?? null,
                'checksum'   => $checksum,
                'size'       => strlen($content),
                'lines'      => substr_count($content, "\n"),
            ];
        }
        return $result;
    }

    private function runMigrations(bool $force = false): array
    {
        $this->ensureMigrationsTable();
        $pdo     = Database::getInstance();
        $applied = $pdo->query('SELECT filename FROM schema_migrations')
                       ->fetchAll(\PDO::FETCH_COLUMN);
        $applied = array_flip($applied);

        $dir   = $this->getMigrationDir();
        $files = glob($dir . '/*.sql');
        sort($files);

        $results = [];
        foreach ($files as $path) {
            $file = basename($path);
            if (!$force && isset($applied[$file])) {
                $results[] = ['file' => $file, 'status' => 'skipped', 'error' => ''];
                continue;
            }
            $results[] = $this->runSingleMigration($file, $force);
        }
        return $results;
    }

    private function runSingleMigration(string $file, bool $force = false): array
    {
        if (!preg_match('/^[0-9a-z_]+\.sql$/i', $file)) {
            return ['file' => $file, 'status' => 'error', 'error' => 'Invalid filename.'];
        }

        $path = $this->getMigrationDir() . '/' . $file;
        if (!file_exists($path)) {
            return ['file' => $file, 'status' => 'error', 'error' => 'File not found.'];
        }

        $pdo = Database::getInstance();

        if (!$force) {
            $exists = $pdo->prepare('SELECT id FROM schema_migrations WHERE filename=? LIMIT 1');
            $exists->execute([$file]);
            if ($exists->fetch()) {
                return ['file' => $file, 'status' => 'skipped', 'error' => ''];
            }
        }

        $sql = file_get_contents($path);

        // Split on semicolons, skip comments and empty
        $statements = array_filter(
            array_map('trim', preg_split('/;(?=(?:[^\'"]|\'[^\']*\'|"[^"]*")*$)/', $sql)),
            fn($s) => $s !== '' && !preg_match('/^--/', ltrim($s))
        );

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $errors = [];
        foreach ($statements as $stmt) {
            try {
                $pdo->exec($stmt);
            } catch (\PDOException $e) {
                // Ignore "already exists" type errors — migrations are idempotent
                $code = $e->getCode();
                if (!in_array($code, ['42S01','42S21','42701'], true) &&
                    !str_contains($e->getMessage(), 'Duplicate column') &&
                    !str_contains($e->getMessage(), 'already exists') &&
                    !str_contains($e->getMessage(), 'Duplicate key name')) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        if ($errors) {
            error_log('[DB Upgrader] ' . $file . ': ' . implode(' | ', $errors));
            return ['file' => $file, 'status' => 'error', 'error' => implode(' · ', array_slice($errors, 0, 2))];
        }

        // Record as applied
        $pdo->prepare('INSERT INTO schema_migrations (filename, checksum) VALUES (?,?) ON DUPLICATE KEY UPDATE applied_at=NOW(), checksum=VALUES(checksum)')
            ->execute([$file, md5($sql)]);

        return ['file' => $file, 'status' => 'ran', 'error' => ''];
    }

    private function getDbVersion(): array
    {
        $pdo = Database::getInstance();
        try {
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            $total   = $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
            $all     = count(glob($this->getMigrationDir() . '/*.sql') ?: []);
            return ['server' => $version, 'applied' => (int)$total, 'total' => $all];
        } catch (\Throwable) {
            return ['server' => 'unknown', 'applied' => 0, 'total' => 0];
        }
    }

    private function getTableStats(): array
    {
        $pdo = Database::getInstance();
        try {
            $stmt = $pdo->query(
                "SELECT TABLE_NAME, TABLE_ROWS, 
                        ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 1) AS size_kb,
                        CREATE_TIME, UPDATE_TIME
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                 ORDER BY TABLE_NAME"
            );
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function viewSql(array $params): void
    {
        AuthMiddleware::requireRole(['admin', 'super_admin']);
        $file = basename($this->request->get('file', ''));
        if (!preg_match('/^[0-9a-z_]+\.sql$/i', $file)) {
            $this->json(['error' => 'Invalid file'], 400);
        }
        $path = $this->getMigrationDir() . '/' . $file;
        if (!file_exists($path)) {
            $this->json(['error' => 'Not found'], 404);
        }
        $this->json(['sql' => file_get_contents($path), 'file' => $file]);
    }
}

// Already closed — append inside class before closing brace
