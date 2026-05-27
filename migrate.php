<?php
declare(strict_types=1);

/**
 * LMSAdvisor — Database Migration Runner
 *
 * Usage (from project root):
 *   php migrate.php           → run all pending migrations + seed
 *   php migrate.php migrate   → migrations only
 *   php migrate.php seed      → seed only
 *   php migrate.php fresh     → DROP all tables, then migrate + seed
 *
 * Run from XAMPP shell or any terminal:
 *   cd C:\xampp\htdocs\lmsadvisor-dev
 *   php migrate.php
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/app.php';

$cfg = require __DIR__ . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $cfg['host'], $cfg['port'], $cfg['dbname'], $cfg['charset']
);

// ── Try to connect; if DB doesn't exist, create it first ──────────────────────
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $cfg['host'], $cfg['port']),
        $cfg['user'], $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$cfg['dbname']}`");
    ok("Database `{$cfg['dbname']}` ready.");
} catch (PDOException $e) {
    fail('Cannot connect to MySQL: ' . $e->getMessage());
}

// ── Parse command ─────────────────────────────────────────────────────────────
$cmd = $argv[1] ?? 'all';

if ($cmd === 'fresh') {
    info('Dropping all tables...');
    dropAllTables($pdo, $cfg['dbname']);
    ok('All tables dropped.');
    $cmd = 'all';
}

if (in_array($cmd, ['all', 'migrate'], true)) {
    runMigrations($pdo);
}

if (in_array($cmd, ['all', 'seed'], true)) {
    runSeeds($pdo);
}

info('Done. ✓');

// ── Functions ─────────────────────────────────────────────────────────────────

function runMigrations(PDO $pdo): void
{
    $dir   = __DIR__ . '/database/migrations';
    $files = glob($dir . '/*.sql');
    if (!$files) {
        warn('No migration files found in database/migrations/');
        return;
    }
    sort($files);

    info('Running migrations...');
    foreach ($files as $file) {
        $name = basename($file);
        try {
            $sql = file_get_contents($file);
            // Split on semicolons (handles multi-statement files)
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($s) => $s !== '' && !preg_match('/^--/m', $s) || strlen(trim($s)) > 3
            );
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || str_starts_with($stmt, '--')) continue;
                $pdo->exec($stmt);
            }
            ok("  ✓ $name");
        } catch (PDOException $e) {
            // Ignore "already exists" errors (idempotent migrations use IF NOT EXISTS)
            if (str_contains($e->getMessage(), 'already exists') ||
                str_contains($e->getMessage(), "Duplicate entry")) {
                warn("  ~ $name (skipped: already applied)");
            } else {
                fail("  ✗ $name — " . $e->getMessage());
            }
        }
    }
}

function runSeeds(PDO $pdo): void
{
    $dir   = __DIR__ . '/database/seeds';
    $files = glob($dir . '/*.sql');
    if (!$files) {
        warn('No seed files found in database/seeds/');
        return;
    }
    sort($files);

    info('Running seeds...');
    foreach ($files as $file) {
        $name = basename($file);
        try {
            $sql = file_get_contents($file);
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($s) => trim($s) !== ''
            );
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || str_starts_with($stmt, '--')) continue;
                $pdo->exec($stmt);
            }
            ok("  ✓ $name");
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                warn("  ~ $name (skipped: data already seeded)");
            } else {
                fail("  ✗ $name — " . $e->getMessage());
            }
        }
    }
}

function dropAllTables(PDO $pdo, string $dbname): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '$dbname'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

// ── Output helpers ────────────────────────────────────────────────────────────
function ok(string $msg): void    { echo "\033[32m$msg\033[0m\n"; }
function info(string $msg): void  { echo "\033[36m$msg\033[0m\n"; }
function warn(string $msg): void  { echo "\033[33m$msg\033[0m\n"; }
function fail(string $msg): never { echo "\033[31m$msg\033[0m\n"; exit(1); }
