<?php

declare(strict_types=1);

namespace Esquare\Theme\Machine;

use WP_CLI;

/**
 * Imports the FabLab machines of the legacy site into the "Machines" content type.
 *
 * The content was taken once from https://e-square.marche.be/fablab/equipements/
 * and cleaned up in data/machines-legacy.json; photos are downloaded from the
 * legacy site into the media library.
 */
final class ImportCommand
{
    /** Identifies an imported machine, so a second run never duplicates it. */
    public const META_KEY    = '_esquare_legacy_key';
    public const META_SOURCE = '_esquare_source_url';

    public static function register(): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('esquare machines import', [new self(), 'import']);
        }
    }

    /**
     * Imports the legacy machines. Safe to run again: existing machines are skipped.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : JSON file to import. Defaults to data/machines-legacy.json in the theme.
     *
     * [--update]
     * : Overwrite machines that were already imported (loses edits made in WordPress).
     *
     * [--skip-images]
     * : Do not download the photos.
     *
     * [--dry-run]
     * : Show what would be done without writing anything.
     *
     * ## EXAMPLES
     *
     *     wp esquare machines import
     *     wp esquare machines import --dry-run
     *
     * @param list<string>         $args
     * @param array<string,string> $assoc
     */
    public function import(array $args, array $assoc): void
    {
        $file   = (string) ($assoc['file'] ?? get_theme_file_path('data/machines-legacy.json'));
        $update = isset($assoc['update']);
        $dryRun = isset($assoc['dry-run']);
        $images = ! isset($assoc['skip-images']);

        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (! is_array($data) || ! is_array($data['machines'] ?? null)) {
            WP_CLI::error("Fichier illisible : {$file}");
        }

        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($data['machines'] as $row) {
            $key      = (string) $row['slug'];
            $existing = get_posts([
                'post_type'      => MachinePostType::POST_TYPE,
                'post_status'    => 'any',
                'meta_key'       => self::META_KEY,
                'meta_value'     => $key,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ])[0] ?? 0;

            if ($existing && ! $update) {
                // A photo that failed to download last time is retried.
                $photo = ! $dryRun && $images && self::attachPhoto((int) $existing, $row);
                WP_CLI::log('= déjà importée' . ($photo ? ' (photo ajoutée)' : '') . " : {$row['title']}");
                $counts['skipped']++;
                continue;
            }

            if ($dryRun) {
                WP_CLI::log(($existing ? '~ serait mise à jour : ' : '+ serait créée : ') . $row['title']);
                $counts[$existing ? 'updated' : 'created']++;
                continue;
            }

            $postId = wp_insert_post(wp_slash([
                'ID'           => $existing,
                'post_type'    => MachinePostType::POST_TYPE,
                'post_status'  => 'publish',
                'post_title'   => (string) $row['title'],
                'post_name'    => $key,
                'post_excerpt' => (string) ($row['excerpt'] ?? ''),
                'post_content' => self::blocks($row['content'] ?? []),
                'menu_order'   => (int) ($row['order'] ?? 0),
                'meta_input'   => [
                    self::META_KEY                 => $key,
                    MachinePostType::META_PRICE    => (string) ($row['price'] ?? ''),
                    MachinePostType::META_BOOKING  => (string) ($row['booking'] ?? ''),
                ],
            ]), true);

            if (is_wp_error($postId)) {
                WP_CLI::warning("{$row['title']} : " . $postId->get_error_message());
                continue;
            }

            if ($images) {
                self::attachPhoto((int) $postId, $row);
            }

            WP_CLI::log(($existing ? '~ mise à jour : ' : '+ créée : ') . $row['title']);
            $counts[$existing ? 'updated' : 'created']++;
        }

        WP_CLI::success(sprintf(
            '%s%d créée(s), %d mise(s) à jour, %d déjà présente(s).',
            $dryRun ? '[dry-run] ' : '',
            $counts['created'],
            $counts['updated'],
            $counts['skipped']
        ));
    }

    /**
     * Turns the simplified content of the JSON file into block markup:
     * ["h", text], ["p", html] or ["ul", [html, …]].
     *
     * @param list<array{0:string,1:string|list<string>}> $content
     */
    private static function blocks(array $content): string
    {
        $out = [];
        foreach ($content as [$type, $value]) {
            $out[] = match ($type) {
                'h'  => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html((string) $value) . "</h2>\n<!-- /wp:heading -->",
                'p'  => "<!-- wp:paragraph -->\n<p>" . wp_kses_post((string) $value) . "</p>\n<!-- /wp:paragraph -->",
                'ul' => "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . implode('', array_map(
                    static fn (string $item): string => "<!-- wp:list-item -->\n<li>" . wp_kses_post($item) . "</li>\n<!-- /wp:list-item -->",
                    (array) $value
                )) . "</ul>\n<!-- /wp:list -->",
                default => '',
            };
        }

        return implode("\n\n", array_filter($out));
    }

    /**
     * Sets the legacy photo as featured image when the machine has none.
     *
     * @param array<string,mixed> $row
     */
    private static function attachPhoto(int $postId, array $row): bool
    {
        $image = (string) ($row['image'] ?? '');
        if ($image === '' || has_post_thumbnail($postId)) {
            return false;
        }

        $attachment = self::sideload($image, $postId, (string) $row['title']);
        if (is_wp_error($attachment)) {
            WP_CLI::warning("{$row['title']} : photo non importée (" . $attachment->get_error_message() . ')');

            return false;
        }

        return (bool) set_post_thumbnail($postId, $attachment);
    }

    /** Downloads a photo once; a second import reuses the attachment. */
    private static function sideload(string $url, int $postId, string $title): int|\WP_Error
    {
        $known = get_posts([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'meta_key'       => self::META_SOURCE,
            'meta_value'     => $url,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ])[0] ?? 0;
        if ($known) {
            return (int) $known;
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // The legacy site can resolve to an internal address from this server,
        // which WordPress refuses by default: allow that one host for this download.
        $host  = (string) wp_parse_url($url, PHP_URL_HOST);
        $allow = static fn (bool $external, string $requested): bool => $external || $requested === $host;
        add_filter('http_request_host_is_external', $allow, 10, 2);
        $id = media_sideload_image($url, $postId, $title, 'id');
        remove_filter('http_request_host_is_external', $allow, 10);
        if (is_wp_error($id)) {
            return $id;
        }

        update_post_meta((int) $id, self::META_SOURCE, $url);

        return (int) $id;
    }
}
