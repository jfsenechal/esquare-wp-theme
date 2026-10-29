<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

const ESQUARE_FONTS_URL = 'https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Figtree:wght@400;500;600;700;800;900&family=Geist+Mono:wght@400;500;600&display=swap';

add_action('after_setup_theme', static function (): void {
    add_theme_support('wp-block-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    // Editor styles also reach the Custom HTML block preview iframe, so the
    // Tailwind markup renders in the editor as it does on the site.
    add_editor_style([
        ESQUARE_FONTS_URL,
        'style.css',
        'assets/css/tailwind.css',
    ]);
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    load_theme_textdomain('esquare', __DIR__ . '/languages');
});

add_action('init', static function (): void {
    register_block_pattern_category('esquare', [
        'label'       => __('e-Square', 'esquare'),
        'description' => __("Modèles spécifiques à l'e-Square Marche-en-Famenne.", 'esquare'),
    ]);

    Esquare\Theme\Block\CategoryArchive::register();

    Esquare\Theme\Reservation\ReservationForm::register();

    Esquare\Theme\Block\Agenda::register();

    Esquare\Theme\Block\ActivityDetail::register();

    Esquare\Theme\Block\Machines::register();

    // JS editor blocks, built from blocks-src/ by `npm run build`.
    foreach (['hero', 'contact', 'horaires', 'cartes', 'cta', 'texte-image', 'tarifs', 'bande-photo'] as $blockName) {
        $blockType = register_block_type(__DIR__ . '/blocks/' . $blockName);
        if (! $blockType instanceof WP_Block_Type) {
            continue;
        }
        // Block styles are versioned with block.json's "version" (scripts use the
        // build hash), so rebuilt CSS would stay cached: version it by file date.
        foreach ($blockType->editor_style_handles as $handle) {
            $style = wp_styles()->query($handle);
            $path  = $style ? ($style->extra['path'] ?? '') : '';
            if ($path !== '' && is_file($path)) {
                $style->ver = (string) filemtime($path);
            }
        }
    }

    add_rewrite_rule('^actualites/?$', 'index.php?category_name=actualites', 'top');
    add_rewrite_rule('^actualites/page/([0-9]+)/?$', 'index.php?category_name=actualites&paged=$matches[1]', 'top');
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('esquare-fonts', ESQUARE_FONTS_URL, [], null);

    $stylePath = get_stylesheet_directory() . '/style.css';
    wp_enqueue_style(
        'esquare-theme',
        get_stylesheet_uri(),
        ['esquare-fonts'],
        is_file($stylePath) ? (string) filemtime($stylePath) : false
    );

    // Built by bin/build-css.sh from assets/css/tailwind.input.css.
    $tailwindPath = get_theme_file_path('assets/css/tailwind.css');
    wp_enqueue_style(
        'esquare-tailwind',
        get_theme_file_uri('assets/css/tailwind.css'),
        ['esquare-theme'],
        is_file($tailwindPath) ? (string) filemtime($tailwindPath) : false
    );

    if (! is_category()) {
        return;
    }

    $modulePath = get_theme_file_path('assets/js/category-filter.js');
    if (! is_file($modulePath)) {
        return;
    }

    wp_enqueue_script_module(
        Esquare\Theme\Block\CategoryArchive::MODULE_HANDLE,
        get_theme_file_uri('assets/js/category-filter.js'),
        ['@wordpress/interactivity'],
        (string) filemtime($modulePath)
    );
});

add_action('wp_head', static function (): void {
    echo "<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
    echo "<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
}, 1);

add_action('wp_head', static function (): void {
    echo "<!-- ESQ_FAVICON_HOOK_FIRED -->\n";
    $iconId  = (int) get_option('site_icon');
    $base    = $iconId > 0 ? (string) wp_get_attachment_url($iconId) : '';
    $size32  = $iconId > 0 ? (string) wp_get_attachment_image_url($iconId, [32, 32]) : '';
    $size192 = $iconId > 0 ? (string) wp_get_attachment_image_url($iconId, [192, 192]) : '';
    $size180 = $iconId > 0 ? (string) wp_get_attachment_image_url($iconId, [180, 180]) : $base;

    $icon32  = $size32 !== '' ? $size32 : $base;
    $icon192 = $size192 !== '' ? $size192 : $base;
    $icon180 = $size180 !== '' ? $size180 : $base;

    if ($icon32 !== '') {
        printf('<link rel="icon" href="%s" sizes="32x32" />' . "\n", esc_url($icon32));
    }
    if ($icon192 !== '') {
        printf('<link rel="icon" href="%s" sizes="192x192" />' . "\n", esc_url($icon192));
    }
    if ($icon180 !== '') {
        printf('<link rel="apple-touch-icon" href="%s" />' . "\n", esc_url($icon180));
    }
}, 2);
