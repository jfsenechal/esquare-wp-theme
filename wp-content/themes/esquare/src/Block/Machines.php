<?php

declare(strict_types=1);

namespace Esquare\Theme\Block;

use Esquare\Theme\Machine\MachinePostType;

/**
 * Server-rendered "Le parc de machines" section of the Fablab page, fed by the
 * "Machines" content type (Esquare\Theme\Machine\MachinePostType).
 *
 * Machines with a photo get a card; the others go to the compact "Et aussi au
 * FabLab" list. Adding a photo to a machine promotes it.
 */
final class Machines
{
    public const BLOCK_NAME = 'esquare/machines';

    public static function register(): void
    {
        register_block_type(self::BLOCK_NAME, [
            'api_version'     => 3,
            'title'           => __('Parc de machines', 'esquare'),
            'category'        => 'theme',
            'render_callback' => [self::class, 'render'],
            // Lets the editor register this PHP-only block and preview it server-side.
            'supports'        => ['autoRegister' => true],
        ]);
    }

    public static function render(array $attrs = [], string $content = ''): string
    {
        $machines = MachinePostType::published();

        $featured = array_values(array_filter($machines, static fn (\WP_Post $m): bool => has_post_thumbnail($m)));
        $others   = array_values(array_filter($machines, static fn (\WP_Post $m): bool => ! has_post_thumbnail($m)));

        ob_start();
        require __DIR__ . '/machines-template.php';

        return (string) ob_get_clean();
    }

    /** Whether the machine has a sheet worth linking to (some only have a name and a line). */
    public static function hasSheet(\WP_Post $machine): bool
    {
        return trim($machine->post_content) !== '';
    }
}
