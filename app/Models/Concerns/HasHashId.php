<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Provides reversible, obfuscated public IDs for Eloquent models.
 *
 * Usage in a model:
 *   use App\Models\Concerns\HasHashId;
 *
 * Static helpers (usable anywhere):
 *   HasHashId::hashId(42)          → e.g. "k7mX"
 *   HasHashId::decodeHashId('k7mX') → 42
 *
 * Algorithm: XOR the integer with a derived salt, then encode with base62.
 * The salt is derived from APP_KEY so it is stable per environment but
 * never exposes the raw DB integer to clients.
 */
trait HasHashId
{
    private static string|null $_hashSalt = null;

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Return the obfuscated public ID for this model instance.
     */
    public function getHashId(): string
    {
        return static::hashId((int) $this->getKey());
    }

    /**
     * Encode any integer into a short alphanumeric hash string.
     */
    public static function hashId(int $id): string
    {
        $salt  = static::salt();
        $xored = $id ^ $salt;

        return static::toBase62($xored);
    }

    /**
     * Decode a hash string back to the original integer ID.
     * Returns 0 if the input is invalid.
     */
    public static function decodeHashId(string $hash): int
    {
        $xored = static::fromBase62($hash);
        $salt  = static::salt();

        return $xored ^ $salt;
    }

    // ── Internals ─────────────────────────────────────────────────────────

    private static function salt(): int
    {
        if (static::$_hashSalt !== null) {
            return (int) static::$_hashSalt;
        }

        $appKey = config('app.key', '');

        // Strip the "base64:" prefix if present.
        if (str_starts_with($appKey, 'base64:')) {
            $raw = base64_decode(substr($appKey, 7));
        } else {
            $raw = $appKey;
        }

        // Take the first 4 bytes of the key as an unsigned 32-bit integer.
        $bytes = substr($raw, 0, 4);
        $int   = 0;
        for ($i = 0; $i < strlen($bytes); $i++) {
            $int = ($int << 8) | ord($bytes[$i]);
        }

        // Keep it positive and non-zero.
        static::$_hashSalt = (string) (abs($int) | 1);

        return (int) static::$_hashSalt;
    }

    private static function toBase62(int $num): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base  = 62;

        if ($num === 0) {
            return '0';
        }

        // Handle negative numbers (XOR can produce negatives on 32-bit).
        $negative = $num < 0;
        $num      = abs($num);
        $result   = '';

        while ($num > 0) {
            $result = $chars[$num % $base] . $result;
            $num    = intdiv($num, $base);
        }

        return $negative ? '-' . $result : $result;
    }

    private static function fromBase62(string $str): int
    {
        $chars  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base   = 62;
        $result = 0;

        $negative = str_starts_with($str, '-');
        if ($negative) {
            $str = substr($str, 1);
        }

        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $pos    = strpos($chars, $str[$i]);
            if ($pos === false) {
                return 0; // invalid character → safe fallback
            }
            $result = $result * $base + $pos;
        }

        return $negative ? -$result : $result;
    }
}
