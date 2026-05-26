<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

if (! defined('ABSPATH')) {
    exit;
}

$esquareAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
if (! is_file($esquareAutoload)) {
    throw new RuntimeException(
        'Esquare theme: composer dependencies are missing. Run `composer install` at the repo root.'
    );
}
require_once $esquareAutoload;

$esquareEnvFile = dirname(__DIR__, 3) . '/.env';
if (is_file($esquareEnvFile)) {
    (new Dotenv())->loadEnv($esquareEnvFile);
}

unset($esquareAutoload, $esquareEnvFile);

add_action('after_setup_theme', static function (): void {
    add_theme_support('wp-block-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
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
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'esquare-fonts',
        'https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Figtree:wght@400;500;600;700;800;900&family=Geist+Mono:wght@400;500;600&display=swap',
        [],
        null
    );

    $stylePath = get_stylesheet_directory() . '/style.css';
    wp_enqueue_style(
        'esquare-theme',
        get_stylesheet_uri(),
        ['esquare-fonts'],
        is_file($stylePath) ? (string) filemtime($stylePath) : false
    );

    wp_enqueue_script(
        'esquare-tailwind',
        'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4',
        [],
        null,
        false
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
    ?>
<style type="text/tailwindcss">
    @theme {
        --color-white: oklch(0.992 0.004 78);
        --color-black: oklch(0.18 0.012 250);
        --color-navy: #0E1F33;
        --color-navy-soft: #1A2D4A;
        --color-navy-deep: #081523;
        --color-yellow: #F2B33D;
        --color-yellow-soft: #FDE3A6;
        --color-cream: #FAF8F3;
        --color-stone-warm: #F1ECE3;
        --font-sans: "Figtree", system-ui, sans-serif;
        --font-serif: "Instrument Serif", Georgia, serif;
        --font-figtree: "Figtree", system-ui, sans-serif;
        --font-mono: "Geist Mono", ui-monospace, monospace;
    }
    @utility poster-grid {
        background-image:
            linear-gradient(color-mix(in oklch, var(--color-navy-deep) 50%, transparent) 1px, transparent 1px),
            linear-gradient(90deg, color-mix(in oklch, var(--color-navy-deep) 50%, transparent) 1px, transparent 1px);
        background-size: 88px 88px;
    }
</style>
    <?php
}, 5);
