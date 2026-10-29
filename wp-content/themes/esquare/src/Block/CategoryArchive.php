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
            'title'           => __('Archive de catégorie', 'esquare'),
            'category'        => 'theme',
            'render_callback' => [self::class, 'render'],
            // Lets the editor register this PHP-only block and preview it server-side.
            'supports'        => ['autoRegister' => true],
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
