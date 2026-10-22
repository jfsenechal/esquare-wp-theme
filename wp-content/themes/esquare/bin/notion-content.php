<?php

declare(strict_types=1);

/**
 * Preview the public body of one activity, exactly as the site would render it.
 *
 *   php wp-content/themes/esquare/bin/notion-content.php <page-id-or-url>
 *
 * Prints the activity title, whether the "DESCRIPTION POUR SITE WEB" section was
 * found and filled in, then the HTML. No WordPress bootstrap, so no cache.
 */

use Esquare\Theme\Notion\ActivityContent;
use Esquare\Theme\Notion\ActivityRepository;
use Esquare\Theme\Notion\NotionConfig;
use Esquare\Theme\Notion\NotionException;
use Symfony\Component\Dotenv\Dotenv;

if (PHP_SAPI !== 'cli') {
    exit("This script is CLI only.\n");
}

$root = dirname(__DIR__, 4);
require_once $root . '/vendor/autoload.php';

if (is_file($root . '/.env')) {
    (new Dotenv())->load($root . '/.env');
}

// A Notion share URL carries the open row as ?p=<id>; prefer it over the database id in the path.
$argument = $argv[1] ?? '';
parse_str((string) parse_url($argument, PHP_URL_QUERY), $query);
$pageId = NotionConfig::normalizeId(is_string($query['p'] ?? null) ? $query['p'] : $argument);

if ($pageId === '') {
    fwrite(STDERR, "Usage: php bin/notion-content.php <page-id-or-url>\n");
    exit(1);
}

try {
    $activity = ActivityRepository::create()->find($pageId);
    if ($activity === null) {
        fwrite(STDERR, "Page not found, or not shared with the integration.\n");
        exit(1);
    }

    $content = ActivityContent::create();
    $section = $content->section($pageId);
    $html    = $content->html($activity);
} catch (NotionException $e) {
    fwrite(STDERR, 'Notion error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo $activity->title, PHP_EOL;
echo 'Section "', ActivityContent::SECTION_LABEL, '": ', $section === [] ? 'missing or placeholder only' : count($section) . ' blocks', PHP_EOL;
echo 'Source: ', $section !== [] ? 'page body' : ($html !== '' ? 'Description publique' : 'nothing to publish'), PHP_EOL, PHP_EOL;
echo $html === '' ? '(empty)' : str_replace(['</p>', '</h3>', '</h4>', '</h5>', '</ul>', '</ol>', '<hr>'], ["</p>\n", "</h3>\n", "</h4>\n", "</h5>\n", "</ul>\n", "</ol>\n", "<hr>\n"], $html), PHP_EOL;
