<?php

declare(strict_types=1);

namespace Esquare\Theme\Block;

use Esquare\Theme\Notion\Machine;
use Esquare\Theme\Notion\MachineRepository;
use Esquare\Theme\Notion\NotionException;

/**
 * Server-rendered "Le parc de machines" section of the Fablab page, fed by the
 * Notion "Machines" database (DB_MATERIALS).
 *
 * Machines with a photo in Notion get a card; the others go to the compact
 * "Et aussi au FabLab" list. Adding a photo in Notion promotes a machine.
 */
final class Machines
{
    public const BLOCK_NAME = 'esquare/machines';

    public static function register(): void
    {
        register_block_type(self::BLOCK_NAME, [
            'api_version'     => 3,
            'render_callback' => [self::class, 'render'],
        ]);
    }

    public static function render(array $attrs = [], string $content = ''): string
    {
        $failed   = false;
        $machines = [];

        try {
            $machines = MachineRepository::create()->published();
        } catch (NotionException $e) {
            // The section stays (the hero links to #equipements) and says so plainly.
            error_log('[esquare] machines: ' . $e->getMessage());
            $failed = true;
        }

        $featured = array_values(array_filter($machines, static fn (Machine $m): bool => $m->coverImage() !== null));
        $others   = array_values(array_filter($machines, static fn (Machine $m): bool => $m->coverImage() === null));

        ob_start();
        require __DIR__ . '/machines-template.php';

        return (string) ob_get_clean();
    }
}
