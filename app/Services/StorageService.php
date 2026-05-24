<?php
declare(strict_types=1);

namespace App\Services;

class StorageService
{
    private static array $allowedMimes = [
        'video'    => ['video/mp4', 'video/webm', 'video/ogg'],
        'document' => ['application/pdf', 'application/msword',
                       'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'image'    => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'material' => ['application/pdf', 'application/zip', 'application/x-zip-compressed',
                       'application/msword',
                       'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                       'application/vnd.ms-excel',
                       'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    ];

    private static array $maxSizes = [
        'video'    => 512 * 1024 * 1024, // 512 MB
        'document' => 50  * 1024 * 1024, // 50 MB
        'image'    => 5   * 1024 * 1024, // 5 MB
        'material' => 100 * 1024 * 1024, // 100 MB
    ];

    /**
     * Handle a file upload.
     *
     * @param string $inputName  $_FILES key
     * @param string $type       video|document|image|material
     * @param string $subDir     e.g. 'videos', 'documents', 'course-materials'
     * @return string            Relative storage path (stored in DB)
     */
    public static function upload(string $inputName, string $type, string $subDir): string
    {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('No file uploaded or upload error.');
        }

        $file = $_FILES[$inputName];
        $mime = mime_content_type($file['tmp_name']);

        $allowed = self::$allowedMimes[$type] ?? [];
        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException("Invalid file type ($mime). Allowed for $type: " . implode(', ', $allowed));
        }

        $maxSize = self::$maxSizes[$type] ?? 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new \RuntimeException('File too large. Max: ' . round($maxSize / 1024 / 1024) . ' MB.');
        }

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $name    = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir     = STORE_PATH . '/uploads/' . trim($subDir, '/') . '/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        return $subDir . '/' . $name;
    }

    /**
     * Delete a stored file by relative path.
     */
    public static function delete(string $relativePath): void
    {
        $abs = STORE_PATH . '/uploads/' . ltrim($relativePath, '/');
        if (file_exists($abs)) {
            @unlink($abs);
        }
    }

    /**
     * Serve a protected file to the browser (for course materials).
     */
    public static function serve(string $relativePath, string $filename = ''): never
    {
        $abs = STORE_PATH . '/uploads/' . ltrim($relativePath, '/');
        if (!file_exists($abs)) {
            http_response_code(404);
            exit('File not found.');
        }
        $mime     = mime_content_type($abs) ?: 'application/octet-stream';
        $basename = $filename ?: basename($abs);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($basename) . '"');
        header('Content-Length: ' . filesize($abs));
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }
}
