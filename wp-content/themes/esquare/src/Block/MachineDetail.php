<?php

declare(strict_types=1);

namespace Esquare\Theme\Block;

use Esquare\Theme\Machine\MachinePostType;

/**
 * Sheet of one machine, at /fablab/equipements/<slug>/.
 *
 * Used by templates/single-esquare_machine.html: title, photo, practical
 * infos (tarif, réservation) and the content written in the editor.
 */
final class MachineDetail
{
    public const BLOCK_NAME = 'esquare/machine';

    public static function register(): void
    {
        register_block_type(self::BLOCK_NAME, [
            'api_version'     => 3,
            'title'           => __('Fiche d’une machine', 'esquare'),
            'category'        => 'theme',
            'render_callback' => [self::class, 'render'],
            'supports'        => ['autoRegister' => true, 'inserter' => false],
        ]);

        add_action('wp_head', [self::class, 'metaDescription'], 3);
    }

    public static function render(array $attrs = [], string $content = ''): string
    {
        $machine = get_queried_object();
        if (! $machine instanceof \WP_Post || $machine->post_type !== MachinePostType::POST_TYPE) {
            return '';
        }

        // Same pipeline as core/post-content (blocks, embeds, typography).
        $body = (string) apply_filters('the_content', $machine->post_content);

        ob_start();
        require __DIR__ . '/machine-detail-template.php';

        return (string) ob_get_clean();
    }

    public static function metaDescription(): void
    {
        if (! is_singular(MachinePostType::POST_TYPE)) {
            return;
        }

        $excerpt = trim((string) get_queried_object()?->post_excerpt);
        if ($excerpt !== '') {
            printf('<meta name="description" content="%s" />' . "\n", esc_attr(wp_strip_all_tags($excerpt)));
        }
    }
}
