<?php

declare(strict_types=1);

namespace Esquare\Theme\Block;

use Esquare\Theme\Notion\Activity;
use Esquare\Theme\Notion\ActivityContent;
use Esquare\Theme\Notion\ActivityRepository;
use Esquare\Theme\Notion\NotionConfig;
use Esquare\Theme\Notion\NotionException;

/**
 * Detail page of one Notion activity, at /agenda/<slug>-<notion-id>/.
 *
 * There is no WordPress post behind an activity: the URL is rewritten onto the
 * "agenda" page with the Notion id in a query var, and the block theme picks
 * templates/activity.html, which holds the `esquare/activity` block.
 *
 * The id alone identifies the activity; the slug is cosmetic and redirected to
 * its current form when the title changes in Notion. Only rows with the public
 * status are shown, anything else is a 404.
 */
final class ActivityDetail
{
    public const BLOCK_NAME = 'esquare/activity';
    public const QUERY_VAR  = 'esquare_activity';
    public const TEMPLATE   = 'activity';

    /** Bump when the rewrite rule changes, so it is flushed once on deploy. */
    private const REWRITE_VERSION = '1';
    private const REWRITE_OPTION  = 'esquare_activity_rewrite_version';

    /** @var array{activity:?Activity,failed:bool}|null Resolved once per request. */
    private static ?array $current = null;

    public static function register(): void
    {
        register_block_type(self::BLOCK_NAME, [
            'api_version'     => 3,
            'title'           => __('Détail d’une activité', 'esquare'),
            'category'        => 'theme',
            'render_callback' => [self::class, 'render'],
            // Lets the editor register this PHP-only block and preview it server-side.
            'supports'        => ['autoRegister' => true],
        ]);

        add_rewrite_rule(
            '^agenda/(?:[^/]*-)?([0-9a-f]{32})/?$',
            'index.php?pagename=agenda&' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );

        add_filter('query_vars', static fn (array $vars): array => [...$vars, self::QUERY_VAR]);
        add_action('init', [self::class, 'flushRewriteRulesOnce'], 99);
        add_action('template_redirect', [self::class, 'resolveRequest']);
        add_filter('page_template_hierarchy', [self::class, 'templateHierarchy']);
        add_filter('redirect_canonical', static fn ($redirect) => self::requestedId() === '' ? $redirect : false);
        add_filter('get_canonical_url', [self::class, 'canonicalUrl']);
        add_filter('document_title_parts', [self::class, 'documentTitle']);
        add_action('wp_head', [self::class, 'metaDescription'], 3);
    }

    /** Public URL of an activity, e.g. /agenda/atelier-numerique-recreatic-3d64…/. */
    public static function url(Activity $activity): string
    {
        $slug = sanitize_title(AgendaFormat::title($activity));
        $id   = NotionConfig::normalizeId($activity->id);

        return home_url(user_trailingslashit('agenda/' . ($slug !== '' ? $slug . '-' : '') . $id));
    }

    public static function flushRewriteRulesOnce(): void
    {
        if (get_option(self::REWRITE_OPTION) !== self::REWRITE_VERSION) {
            flush_rewrite_rules(false);
            update_option(self::REWRITE_OPTION, self::REWRITE_VERSION, true);
        }
    }

    /**
     * Decide the response before any output: 404 for unknown or unpublished
     * activities, 301 to the current slug, 503 when Notion is unreachable.
     */
    public static function resolveRequest(): void
    {
        if (self::requestedId() === '') {
            return;
        }

        ['activity' => $activity, 'failed' => $failed] = self::current();

        if ($failed) {
            status_header(503);
            header('Retry-After: 300');
            nocache_headers();

            return;
        }

        if ($activity === null) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();

            return;
        }

        $canonical = self::url($activity);
        $requested = home_url(add_query_arg([]));
        if (untrailingslashit((string) wp_parse_url($canonical, PHP_URL_PATH)) !== untrailingslashit((string) wp_parse_url($requested, PHP_URL_PATH))) {
            wp_safe_redirect($canonical, 301);
            exit;
        }
    }

    /** @param list<string> $templates */
    public static function templateHierarchy(array $templates): array
    {
        return self::requestedId() === '' ? $templates : [self::TEMPLATE . '.php', ...$templates];
    }

    public static function canonicalUrl(string $url): string
    {
        $activity = self::requestedId() === '' ? null : self::current()['activity'];

        return $activity === null ? $url : self::url($activity);
    }

    /** @param array<string,string> $parts */
    public static function documentTitle(array $parts): array
    {
        $activity = self::requestedId() === '' ? null : self::current()['activity'];
        if ($activity !== null) {
            $parts['title'] = AgendaFormat::title($activity);
        }

        return $parts;
    }

    public static function metaDescription(): void
    {
        $activity = self::requestedId() === '' ? null : self::current()['activity'];
        if ($activity === null || trim($activity->description) === '') {
            return;
        }

        printf(
            '<meta name="description" content="%s">' . "\n",
            esc_attr(wp_trim_words(wp_strip_all_tags($activity->description), 30, '…'))
        );
    }

    public static function render(array $attrs = [], string $content = ''): string
    {
        if (self::requestedId() === '') {
            return '';
        }

        ['activity' => $activity, 'failed' => $failed] = self::current();
        if ($activity === null && ! $failed) {
            return '';
        }

        $body = '';
        if ($activity !== null) {
            try {
                $body = ActivityContent::create()->html($activity);
            } catch (NotionException $e) {
                // The properties are already here; losing only the body is no reason to fail the page.
                error_log('[esquare] activity content: ' . $e->getMessage());
                $body = ActivityContent::descriptionHtml($activity);
            }
        }

        ob_start();
        require __DIR__ . '/activity-detail-template.php';

        return (string) ob_get_clean();
    }

    /** @return array{activity:?Activity,failed:bool} */
    private static function current(): array
    {
        if (self::$current !== null) {
            return self::$current;
        }

        $activity = null;
        $failed   = false;

        try {
            $activity = ActivityRepository::create()->find(self::requestedId());
        } catch (NotionException $e) {
            error_log('[esquare] activity: ' . $e->getMessage());
            $failed = true;
        }

        // Rows exist in Notion long before they are announced: same rule as the agenda.
        if ($activity !== null && ! $activity->isPublic()) {
            $activity = null;
        }

        return self::$current = ['activity' => $activity, 'failed' => $failed];
    }

    private static function requestedId(): string
    {
        return NotionConfig::normalizeId((string) get_query_var(self::QUERY_VAR));
    }
}
