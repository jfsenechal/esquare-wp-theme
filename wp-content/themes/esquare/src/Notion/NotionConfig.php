<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Single place that reads the Notion settings out of `.env`.
 *
 * `.env` is loaded by wp-config.php (Symfony Dotenv), which populates $_ENV /
 * $_SERVER; getenv() is checked first so a real environment variable — Docker,
 * Apache SetEnv, CI — always wins over the file.
 */
final class NotionConfig
{
    public const KEY_API      = 'NOTION_API_KEY';
    public const KEY_VERSION  = 'NOTION_VERSION';
    public const KEY_ACTIVITIES = 'DB_ACTIVITIES';

    public static function apiKey(): string
    {
        return self::env(self::KEY_API);
    }

    public static function version(): string
    {
        return self::env(self::KEY_VERSION, NotionClient::DEFAULT_VERSION);
    }

    /** Raw DB_ACTIVITIES value, normalised to a dashless database id. */
    public static function activitiesDatabaseId(): string
    {
        return self::normalizeId(self::env(self::KEY_ACTIVITIES));
    }

    public static function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    /**
     * Notion accepts a database/page id with or without dashes, and copy-pasted
     * share URLs embed it as the last 32 hex characters. Reduce all of those to
     * the bare 32-character form, or return '' when the value is not an id at all.
     */
    public static function normalizeId(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        // https://www.notion.so/workspace/<title>-<32 hex>?v=… → keep the id.
        if (preg_match('/([0-9a-fA-F]{32})/', str_replace('-', '', $value), $m) === 1) {
            return strtolower($m[1]);
        }

        return '';
    }

    /** Dashed UUID form, which is what Notion echoes back in its payloads. */
    public static function dashedId(string $id): string
    {
        $id = self::normalizeId($id);

        return $id === '' ? '' : implode('-', [
            substr($id, 0, 8),
            substr($id, 8, 4),
            substr($id, 12, 4),
            substr($id, 16, 4),
            substr($id, 20, 12),
        ]);
    }

    public static function env(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }

        return trim((string) $value);
    }
}
