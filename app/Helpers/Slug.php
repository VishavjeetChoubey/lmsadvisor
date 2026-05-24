<?php
declare(strict_types=1);

namespace App\Helpers;

class Slug
{
    public static function make(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Make slug unique against a DB column.
     * Appends -1, -2 etc. if needed.
     */
    public static function unique(string $base, string $table, string $column, ?int $excludeId = null): string
    {
        $pdo   = \App\Core\Database::getInstance();
        $slug  = self::make($base);
        $final = $slug;
        $i     = 1;

        while (true) {
            $sql    = "SELECT id FROM `$table` WHERE `$column` = ?";
            $params = [$final];
            if ($excludeId !== null) {
                $sql    .= ' AND id != ?';
                $params[] = $excludeId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if (!$stmt->fetch()) break;
            $final = $slug . '-' . $i++;
        }

        return $final;
    }
}
