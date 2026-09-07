<?php

declare(strict_types=1);

namespace Esquare\Theme\Block;

use Esquare\Theme\Notion\Activity;
use Esquare\Theme\Notion\ActivityRepository;
use Esquare\Theme\Notion\NotionException;

/**
 * Server-rendered agenda fed by the Notion "Activités" database.
 *
 * Two variants share one data source and one row partial:
 *  - `home`: the short band under "Nos événements & ateliers" on the front page.
 *  - `page`: the full list, grouped by month, used by templates/page-agenda.html.
 *
 * Only activities whose Notion status is "Date validée (Public)" are published;
 * see Activity::STATUS_PUBLISHED.
 */
final class Agenda
{
    public const BLOCK_NAME = 'esquare/agenda';

    /** Rows shown in the front-page band. */
    public const HOME_LIMIT = 6;

    public const AGENDA_URL = '/agenda/';

    public static function register(): void
    {
        register_block_type(self::BLOCK_NAME, [
            'api_version'     => 3,
            'render_callback' => [self::class, 'render'],
            'attributes'      => [
                'variant' => ['type' => 'string', 'default' => 'home'],
                'limit'   => ['type' => 'number', 'default' => 0],
            ],
        ]);
    }

    public static function render(array $attrs = [], string $content = ''): string
    {
        $variant = ($attrs['variant'] ?? 'home') === 'page' ? 'page' : 'home';
        $limit   = (int) ($attrs['limit'] ?? 0);

        if ($limit <= 0) {
            $limit = $variant === 'home' ? self::HOME_LIMIT : 0;
        }

        $failed     = false;
        $activities = [];

        try {
            $activities = ActivityRepository::create()->upcomingPublished($limit > 0 ? $limit : null);
        } catch (NotionException $e) {
            // Notion being unreachable must never take the page down: the band
            // disappears, the dedicated page says so plainly.
            error_log('[esquare] agenda: ' . $e->getMessage());
            $failed = true;
        }

        // An empty band on the home page is noise; the agenda page owns the
        // empty and error states because that is what the visitor came for.
        if ($variant === 'home' && ($failed || $activities === [])) {
            return '';
        }

        $format = AgendaFormat::class;

        ob_start();
        require __DIR__ . ($variant === 'page' ? '/agenda-page-template.php' : '/agenda-home-template.php');

        return (string) ob_get_clean();
    }

    /**
     * Renders one row. Kept here so both variants stay pixel-identical.
     *
     * @param list<Activity> $activities
     */
    public static function rows(array $activities): void
    {
        foreach ($activities as $activity) {
            require __DIR__ . '/agenda-row.php';
        }
    }
}
