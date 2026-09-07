<?php

declare(strict_types=1);

/**
 * Standalone smoke test for the Notion library — no WordPress bootstrap needed.
 *
 *   php wp-content/themes/esquare/bin/notion-check.php
 *   php wp-content/themes/esquare/bin/notion-check.php <database-id-or-url>
 *
 * It checks the token, lists the databases shared with the integration, then
 * dumps the schema and the first rows of the activities database.
 */

use Esquare\Theme\Notion\ActivityRepository;
use Esquare\Theme\Notion\DatabaseRepository;
use Esquare\Theme\Notion\NotionClient;
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

$rowLimit = 5;

function line(string $label, string $value = ''): void
{
    // mb_str_pad: the Notion labels are accented, str_pad would count bytes.
    echo mb_str_pad($label, 30) . $value . PHP_EOL;
}

function mask(string $secret): string
{
    return strlen($secret) > 12
        ? substr($secret, 0, 8) . str_repeat('.', 6) . substr($secret, -4)
        : '(too short)';
}

echo PHP_EOL . "== Notion configuration ==" . PHP_EOL;
line('NOTION_API_KEY', NotionConfig::apiKey() === '' ? 'MISSING' : mask(NotionConfig::apiKey()));
line('Notion-Version', NotionConfig::version());

$rawActivities = NotionConfig::env(NotionConfig::KEY_ACTIVITIES);
$activitiesId  = $argv[1] ?? NotionConfig::activitiesDatabaseId();
$activitiesId  = NotionConfig::normalizeId($activitiesId);

line('DB_ACTIVITIES', $rawActivities === '' ? 'MISSING' : mask($rawActivities));
line('  → database id', $activitiesId === '' ? 'NOT A NOTION ID' : NotionConfig::dashedId($activitiesId));

echo PHP_EOL . "== Connection ==" . PHP_EOL;

try {
    $client = NotionClient::fromEnv();
    $me     = $client->me();
} catch (NotionException $e) {
    line('FAILED', $e->getMessage());
    exit(1);
}

line('Authenticated as', (string) ($me['name'] ?? $me['id'] ?? '?'));
line('Bot type', (string) ($me['type'] ?? '?'));
line('Workspace', (string) ($me['bot']['workspace_name'] ?? 'n/a'));

$repository = new DatabaseRepository($client);

echo PHP_EOL . "== Databases shared with the integration ==" . PHP_EOL;

try {
    $databases = $repository->listDatabases();
    if ($databases === []) {
        echo "  (none — share the database with the integration in Notion: ••• → Connections)" . PHP_EOL;
    }
    foreach ($databases as $database) {
        line('  ' . ($database['title'] !== '' ? $database['title'] : '(untitled)'), $database['id']);
    }
} catch (NotionException $e) {
    line('  search failed', $e->getMessage());
    $databases = [];
}

// Fall back to a shared database whose title looks like the activities one, so
// the data check still runs when DB_ACTIVITIES holds a wrong value.
if ($activitiesId === '') {
    foreach ($databases as $database) {
        if (stripos($database['title'], 'activit') !== false) {
            $activitiesId = NotionConfig::normalizeId($database['id']);
            echo PHP_EOL . '  ! DB_ACTIVITIES is unusable; falling back to "' . $database['title'] . '"' . PHP_EOL;
            break;
        }
    }
}

if ($activitiesId === '') {
    echo PHP_EOL . "No usable activities database id — set DB_ACTIVITIES in .env." . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "== Activities database ==" . PHP_EOL;

try {
    line('Title', $repository->title($activitiesId));

    foreach ($repository->schema($activitiesId) as $name => $type) {
        line('  ' . $name, $type);
    }

    $rows = $repository->rows($activitiesId, limit: $rowLimit);
    echo PHP_EOL . '== First ' . count($rows) . ' row(s) ==' . PHP_EOL;

    foreach ($rows as $index => $row) {
        echo PHP_EOL . '#' . ($index + 1) . ' ' . $row['id'] . PHP_EOL;
        foreach ($row['properties'] as $name => $value) {
            line('  ' . $name, is_scalar($value) || $value === null
                ? var_export($value, true)
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }
} catch (NotionException $e) {
    echo PHP_EOL . 'FAILED: ' . $e->getMessage() . PHP_EOL;
    if ($e->isNotFound()) {
        echo 'The integration cannot see this database — share it from Notion (••• → Connections).' . PHP_EOL;
    }
    exit(1);
}

echo PHP_EOL . '== ActivityRepository ==' . PHP_EOL;

try {
    $activities = new ActivityRepository($repository, $activitiesId);

    $upcoming = $activities->upcoming($rowLimit);
    line('Upcoming', (string) count($upcoming) . ' (limit ' . $rowLimit . ')');

    foreach ($upcoming as $activity) {
        line(
            '  ' . ($activity->start?->format('d/m/Y H:i') ?? 'sans date'),
            $activity->title
            . ' [' . $activity->status . ($activity->isPublic() ? ' / public' : ' / prive') . ']'
        );
    }

    line('Publiees a venir', (string) count($activities->upcomingPublished($rowLimit)));
} catch (NotionException $e) {
    echo 'FAILED: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo PHP_EOL . 'OK' . PHP_EOL;
