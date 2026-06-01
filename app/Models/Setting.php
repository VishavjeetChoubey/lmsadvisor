<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected string $table = 'settings';

    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $instance = new self();
        $row = $instance->queryOne(
            'SELECT value FROM settings WHERE `key` = ? LIMIT 1',
            [$key]
        );

        $val = $row ? $row['value'] : $default;
        self::$cache[$key] = $val;
        return $val;
    }

    public static function set(string $key, mixed $value): void
    {
        $instance = new self();
        $instance->execute(
            'INSERT INTO settings (`key`, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [$key, $value]
        );
        self::$cache[$key] = $value;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    public static function group(string $groupName): array
    {
        $instance = new self();
        $rows = $instance->query(
            'SELECT `key`, value, type, label FROM settings WHERE group_name = ? ORDER BY id',
            [$groupName]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row;
        }
        return $result;
    }
}
