<?php
declare(strict_types=1);

namespace App\Helpers;

class Encryption
{
    private static string $cipher = 'AES-256-CBC';

    private static function key(): string
    {
        $k = env('APP_KEY', '');
        // Pad/hash to exactly 32 bytes
        return substr(hash('sha256', $k, true), 0, 32);
    }

    public static function encrypt(string $value): string
    {
        $iv        = random_bytes(openssl_cipher_iv_length(self::$cipher));
        $encrypted = openssl_encrypt($value, self::$cipher, self::key(), 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(string $payload): string
    {
        $raw    = base64_decode($payload);
        $ivLen  = openssl_cipher_iv_length(self::$cipher);
        $iv     = substr($raw, 0, $ivLen);
        $enc    = substr($raw, $ivLen);
        return (string)openssl_decrypt($enc, self::$cipher, self::key(), 0, $iv);
    }
}
