<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * ScormService — handles SCORM package extraction, progress tracking,
 * and SCORM 1.2 / 2004 data persistence via AJAX.
 */
class ScormService
{
    /**
     * Extract a SCORM zip into storage/scorm_packages/{lessonId}/
     * Called after a SCORM zip is uploaded to storage/uploads/scorm/
     *
     * @param string $zipRelativePath  Relative path from STORE_PATH e.g. "scorm/abc123.zip"
     * @param int    $lessonId
     * @return string  Path to the extracted index file (relative from web root)
     */
    public static function extract(string $zipRelativePath, int $lessonId): string
    {
        $zipAbs  = STORE_PATH . '/uploads/' . ltrim($zipRelativePath, '/');
        $destDir = STORE_PATH . '/scorm_packages/' . $lessonId . '/';

        if (!file_exists($zipAbs)) {
            throw new \RuntimeException('SCORM zip not found: ' . $zipAbs);
        }

        // Clean existing extraction
        if (is_dir($destDir)) {
            self::rmDir($destDir);
        }
        mkdir($destDir, 0755, true);

        // Try PHP ZipArchive first (XAMPP always has it)
        if (extension_loaded('zip')) {
            $zip = new \ZipArchive();
            if ($zip->open($zipAbs) !== true) {
                throw new \RuntimeException('Failed to open SCORM zip.');
            }
            $zip->extractTo($destDir);
            $zip->close();
        } else {
            // Fallback: shell unzip
            $cmd = 'unzip -o ' . escapeshellarg($zipAbs) . ' -d ' . escapeshellarg($destDir) . ' 2>&1';
            exec($cmd, $output, $code);
            if ($code !== 0) {
                throw new \RuntimeException('unzip failed: ' . implode("\n", $output));
            }
        }

        return self::findEntryPoint($destDir, $lessonId);
    }

    /**
     * Find the SCORM entry point (index.html or imsmanifest.xml launch file)
     */
    public static function findEntryPoint(string $destDir, int $lessonId): string
    {
        // Parse imsmanifest.xml to find the launch resource
        $manifestPath = $destDir . 'imsmanifest.xml';
        if (file_exists($manifestPath)) {
            $xml = @simplexml_load_file($manifestPath);
            if ($xml) {
                // Try to find <resource> with href attribute
                $ns = $xml->getNamespaces(true);
                $resources = $xml->resources ?? $xml->organizations ?? null;

                // XPath search for href in resource elements
                foreach ($xml->resources->resource ?? [] as $res) {
                    $href = (string)($res['href'] ?? '');
                    if ($href && file_exists($destDir . $href)) {
                        return $href;
                    }
                }
            }
        }

        // Common fallbacks
        $candidates = ['index.html','index.htm','story.html','story_html5/index.html',
                       'content/index.html','scormcontent/index.html','launch.html','default.html'];
        foreach ($candidates as $c) {
            if (file_exists($destDir . $c)) return $c;
        }

        // Last resort: first .html file found
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($destDir));
        foreach ($iter as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['html','htm'])) {
                return str_replace($destDir, '', $file->getRealPath());
            }
        }

        throw new \RuntimeException('Could not find SCORM entry point in package.');
    }

    /**
     * Check if a package has been extracted already
     */
    public static function isExtracted(int $lessonId): bool
    {
        return is_dir(STORE_PATH . '/scorm_packages/' . $lessonId . '/');
    }

    /**
     * Get the package directory URL (served via /scorm-player/{lessonId}/...)
     */
    public static function packageUrl(int $lessonId): string
    {
        return APP_URL . '/scorm/' . $lessonId;
    }

    // ── Progress tracking ─────────────────────────────────────────────────────

    public static function saveProgress(int $enrollmentId, int $lessonId, array $data): void
    {
        $pdo  = Database::getInstance();
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        // Check existing
        $existing = $pdo->prepare(
            'SELECT id FROM lesson_progress WHERE enrollment_id=? AND lesson_id=? LIMIT 1'
        );
        $existing->execute([$enrollmentId, $lessonId]);
        $row = $existing->fetch();

        $status = self::detectStatus($data);
        $pct    = self::detectProgress($data);

        if ($row) {
            $pdo->prepare(
                'UPDATE lesson_progress SET status=?, progress_pct=?, scorm_data=?,
                 completed_at=COALESCE(IF(status="completed",NOW(),NULL),completed_at)
                 WHERE enrollment_id=? AND lesson_id=?'
            )->execute([$status, $pct, $json, $enrollmentId, $lessonId]);
        } else {
            // Get user_id
            $enroll = $pdo->prepare('SELECT user_id FROM enrollments WHERE id=? LIMIT 1');
            $enroll->execute([$enrollmentId]);
            $userId = (int)($enroll->fetchColumn() ?: 0);

            $pdo->prepare(
                'INSERT INTO lesson_progress
                 (enrollment_id,lesson_id,user_id,status,progress_pct,scorm_data,started_at,completed_at)
                 VALUES (?,?,?,?,?,?,NOW(),?)'
            )->execute([
                $enrollmentId, $lessonId, $userId, $status, $pct, $json,
                $status === 'completed' ? date('Y-m-d H:i:s') : null,
            ]);
        }
    }

    public static function getProgress(int $enrollmentId, int $lessonId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT scorm_data FROM lesson_progress WHERE enrollment_id=? AND lesson_id=? LIMIT 1'
        );
        $stmt->execute([$enrollmentId, $lessonId]);
        $row = $stmt->fetch();
        if (!$row || !$row['scorm_data']) return [];
        return json_decode($row['scorm_data'], true) ?: [];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function detectStatus(array $data): string
    {
        // SCORM 1.2
        $s = $data['cmi.core.lesson_status'] ?? $data['cmi.completion_status'] ?? '';
        if (in_array($s, ['passed','completed'], true)) return 'completed';
        if ($s === 'failed') return 'failed';
        if ($s === 'incomplete') return 'active';
        // SCORM 2004
        $completion = $data['cmi.completion_status'] ?? '';
        $success    = $data['cmi.success_status'] ?? '';
        if ($completion === 'completed') return 'completed';
        if ($success === 'passed') return 'completed';
        return 'active';
    }

    private static function detectProgress(array $data): int
    {
        // SCORM 1.2 lesson_location often holds percent
        $loc = $data['cmi.core.lesson_location'] ?? '';
        if (is_numeric($loc)) return min(100, (int)$loc);
        // Explicit progress measure
        $m = $data['cmi.progress_measure'] ?? '';
        if (is_numeric($m)) return min(100, (int)round((float)$m * 100));
        // Check completion
        if (self::detectStatus($data) === 'completed') return 100;
        return 0;
    }

    private static function rmDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = array_diff(scandir($dir), ['.','..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? self::rmDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
