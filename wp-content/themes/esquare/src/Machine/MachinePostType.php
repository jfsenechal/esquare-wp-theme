<?php

declare(strict_types=1);

namespace Esquare\Theme\Machine;

/**
 * The FabLab machines, stored in WordPress as the "Machines" content type.
 *
 * One machine = one post, edited like a page:
 *  - title, featured image (the photo), excerpt (the short text of the card);
 *  - content: technical details and links, in ordinary blocks;
 *  - two fields in the "Infos pratiques" sidebar panel (tarif, réservation);
 *  - "Ordre" (menu_order) decides the position in the list.
 *
 * Public URL: /fablab/equipements/<slug>/, the same as on the legacy site.
 */
final class MachinePostType
{
    public const POST_TYPE    = 'esquare_machine';
    public const META_PRICE   = 'esquare_machine_price';
    public const META_BOOKING = 'esquare_machine_booking_url';
    public const URL_BASE     = 'fablab/equipements';
    /** Where the list lives: the machines section of the Fablab page. */
    public const LIST_URL     = '/fablab/#equipements';

    /** Bump when the rewrite rules change, so they are flushed once on deploy. */
    private const REWRITE_VERSION = '1';
    private const REWRITE_OPTION  = 'esquare_machine_rewrite_version';

    public static function register(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'                  => 'Machines',
                'singular_name'         => 'Machine',
                'menu_name'             => 'Machines',
                'add_new'               => 'Ajouter une machine',
                'add_new_item'          => 'Ajouter une machine',
                'edit_item'             => 'Modifier la machine',
                'new_item'              => 'Nouvelle machine',
                'view_item'             => 'Voir la machine',
                'view_items'            => 'Voir les machines',
                'search_items'          => 'Rechercher une machine',
                'not_found'             => 'Aucune machine.',
                'not_found_in_trash'    => 'Aucune machine dans la corbeille.',
                'all_items'             => 'Toutes les machines',
                'featured_image'        => 'Photo de la machine',
                'set_featured_image'    => 'Choisir la photo',
                'remove_featured_image' => 'Retirer la photo',
                'use_featured_image'    => 'Utiliser comme photo',
                'item_published'        => 'Machine publiée.',
                'item_updated'          => 'Machine mise à jour.',
            ],
            'description'   => 'Le parc de machines du FabLab.',
            'public'        => true,
            'has_archive'   => false,
            'show_in_rest'  => true,
            'menu_position' => 21,
            'menu_icon'     => 'dashicons-hammer',
            'supports'      => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions'],
            'rewrite'       => ['slug' => self::URL_BASE, 'with_front' => false],
            // What a new machine starts with, so every sheet has the same shape.
            'template'      => [
                ['core/heading', ['level' => 2, 'content' => 'Caractéristiques techniques']],
                ['core/list', [], [['core/list-item', ['placeholder' => 'Surface de travail, matériaux, formats de fichiers…']]]],
                ['core/heading', ['level' => 2, 'content' => 'Pour aller plus loin']],
                ['core/list', [], [['core/list-item', ['placeholder' => 'Manuel, tutoriels, sites de modèles…']]]],
            ],
        ]);

        foreach ([self::META_PRICE => 'sanitize_text_field', self::META_BOOKING => 'esc_url_raw'] as $key => $sanitize) {
            register_post_meta(self::POST_TYPE, $key, [
                'type'              => 'string',
                'single'            => true,
                'default'           => '',
                'show_in_rest'      => true,
                'sanitize_callback' => $sanitize,
                'auth_callback'     => static fn (): bool => current_user_can('edit_posts'),
            ]);
        }

        // The legacy list URL: the list is now a section of the Fablab page.
        add_rewrite_rule('^' . self::URL_BASE . '/?$', 'index.php?pagename=fablab', 'top');

        add_action('init', [self::class, 'flushRewriteRulesOnce'], 99);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueEditorPanel']);
        add_action('pre_get_posts', [self::class, 'orderAdminList']);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [self::class, 'adminColumns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [self::class, 'adminColumn'], 10, 2);
        add_filter('enter_title_here', static fn (string $text, \WP_Post $post): string => $post->post_type === self::POST_TYPE ? 'Nom de la machine' : $text, 10, 2);
    }

    /**
     * Published machines in display order ("Ordre", then title).
     *
     * @return list<\WP_Post>
     */
    public static function published(): array
    {
        return get_posts([
            'post_type'        => self::POST_TYPE,
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'orderby'          => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'suppress_filters' => false,
        ]);
    }

    public static function price(\WP_Post $machine): string
    {
        return trim((string) get_post_meta($machine->ID, self::META_PRICE, true));
    }

    public static function bookingUrl(\WP_Post $machine): string
    {
        return trim((string) get_post_meta($machine->ID, self::META_BOOKING, true));
    }

    public static function flushRewriteRulesOnce(): void
    {
        if (get_option(self::REWRITE_OPTION) !== self::REWRITE_VERSION) {
            flush_rewrite_rules(false);
            update_option(self::REWRITE_OPTION, self::REWRITE_VERSION, true);
        }
    }

    /** "Infos pratiques" panel of the machine editor (plain JS, no build step). */
    public static function enqueueEditorPanel(): void
    {
        if (get_current_screen()?->post_type !== self::POST_TYPE) {
            return;
        }

        $path = get_theme_file_path('assets/js/machine-editor.js');
        wp_enqueue_script(
            'esquare-machine-editor',
            get_theme_file_uri('assets/js/machine-editor.js'),
            ['wp-plugins', 'wp-editor', 'wp-components', 'wp-core-data', 'wp-data', 'wp-element'],
            is_file($path) ? (string) filemtime($path) : false,
            true
        );
    }

    /** The admin list follows the public order unless a column sort is asked. */
    public static function orderAdminList(\WP_Query $query): void
    {
        if (! is_admin() || ! $query->is_main_query() || $query->get('post_type') !== self::POST_TYPE || $query->get('orderby')) {
            return;
        }

        $query->set('orderby', ['menu_order' => 'ASC', 'title' => 'ASC']);
    }

    /**
     * @param array<string,string> $columns
     *
     * @return array<string,string>
     */
    public static function adminColumns(array $columns): array
    {
        $date = $columns['date'] ?? null;
        unset($columns['date']);

        $columns['esquare_photo'] = 'Photo';
        $columns['esquare_price'] = 'Tarif';
        $columns['esquare_order'] = 'Ordre';
        if ($date !== null) {
            $columns['date'] = $date;
        }

        return $columns;
    }

    public static function adminColumn(string $column, int $postId): void
    {
        $post = get_post($postId);
        if (! $post instanceof \WP_Post) {
            return;
        }

        echo match ($column) {
            'esquare_photo' => get_the_post_thumbnail($postId, [60, 60]) ?: '—',
            'esquare_price' => esc_html(self::price($post) ?: '—'),
            'esquare_order' => (int) $post->menu_order,
            default         => '',
        };
    }
}
