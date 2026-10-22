<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Transient cache shared by every Notion read, with one flush for all of it.
 *
 * Cache keys are hashes of the query, so they cannot be listed or deleted one
 * by one. Every key is prefixed with a generation number instead: flush() bumps
 * the generation, the old entries are never read again and expire on their own.
 * This also works with a persistent object cache, where transients never touch
 * the options table.
 *
 * Outside WordPress (CLI scripts, tests) every call is a no-op / miss.
 */
final class NotionCache
{
    public const PREFIX = 'esquare_notion_';
    public const GENERATION_OPTION = 'esquare_notion_cache_generation';

    /** Cached value, or null on a miss. Values are always arrays. */
    public static function get(string $key): ?array
    {
        if (! function_exists('get_transient')) {
            return null;
        }

        $value = get_transient(self::key($key));

        return is_array($value) ? $value : null;
    }

    public static function set(string $key, array $value, int $ttl): void
    {
        if (function_exists('set_transient')) {
            set_transient(self::key($key), $value, $ttl);
        }
    }

    /** Forget every cached Notion response at once. */
    public static function flush(): void
    {
        if (function_exists('update_option')) {
            update_option(self::GENERATION_OPTION, self::generation() + 1, true);
        }
    }

    public static function generation(): int
    {
        return function_exists('get_option') ? (int) get_option(self::GENERATION_OPTION, 0) : 0;
    }

    /** Transient names are capped at 172 characters; hash anything long. */
    private static function key(string $key): string
    {
        $key = strlen($key) > 120 ? substr($key, 0, 60) . '_' . md5($key) : $key;

        return self::PREFIX . self::generation() . '_' . $key;
    }
}
