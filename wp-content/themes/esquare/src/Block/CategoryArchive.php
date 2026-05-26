<?php

declare(strict_types=1);

namespace Esquare\Theme\Block;

use WP_Term;

final class CategoryArchive
{
    public const BLOCK_NAME = 'esquare/category-archive';
    public const STORE_NAMESPACE = 'esquare/category-filter';
    public const MODULE_HANDLE = '@esquare/category-filter';
    public const POSTS_PER_PAGE = 36;

    public static function register(): void
    {
        register_block_type(self::BLOCK_NAME, [
            'api_version'     => 3,
            'render_callback' => [self::class, 'render'],
        ]);
    }

    public static function render(array $attrs = [], string $content = ''): string
    {
        $term = get_queried_object();
        if (! $term instanceof WP_Term) {
            return '';
        }

        ob_start();
        require __DIR__ . '/category-archive-template.php';
        return (string) ob_get_clean();
    }
}
