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

    /**
     * Decrypt only if the value looks encrypted (valid base64 and decryptable).
     * Safely returns the raw value if it's already a plain-text API key.
     * This handles the case where the admin stored the key without encryption.
     */
    public static function decryptIfNeeded(string $value): string
    {
        if ($value === '') return '';

        // Plain API keys start with recognisable prefixes — return as-is
        if (str_starts_with($value, 'sk-') ||
            str_starts_with($value, 'sk-ant-') ||
            str_starts_with($value, 'sk-proj-')) {
            return $value;
        }

        // Try to decrypt; fall back to raw value on failure
        try {
            $decrypted = self::decrypt($value);
            return $decrypted !== '' ? $decrypted : $value;
        } catch (\Throwable) {
            return $value;
        }
    }
}
