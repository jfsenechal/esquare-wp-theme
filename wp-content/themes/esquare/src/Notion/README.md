# Notion

Client REST Notion pour le thème e-Square.

## Configuration (`.env`)

```dotenv
NOTION_API_KEY=secret_…          # ou ntn_… (token d'intégration interne)
DB_ACTIVITIES=7282d0db-d353-4923-b78d-f4d48b9ca02b   # id de la base "Activités"
#NOTION_VERSION=2022-06-28       # optionnel, voir NotionClient::DEFAULT_VERSION
```

`DB_ACTIVITIES` attend un **id de base** (32 caractères hexadécimaux, avec ou
sans tirets — l'URL de partage Notion est acceptée telle quelle), pas un token.
Chaque base doit être partagée avec l'intégration dans Notion (••• → Connexions).

## Classes

| Classe | Rôle |
| --- | --- |
| `NotionClient` | transport HTTP (Symfony HttpClient), auth, retries 429/5xx, pagination |
| `NotionConfig` | lecture de `.env`, normalisation des ids |
| `DatabaseRepository` | schéma, requêtes, pages, découverte des bases (`listDatabases()`) |
| `PageNormalizer` | aplatit les propriétés Notion en valeurs PHP |
| `Activity` / `ActivityRepository` | la base "Activités", typée et mise en cache (transient, 15 min) |
| `NotionException` | erreurs API (statut HTTP + code Notion) |

## Utilisation

```php
use Esquare\Theme\Notion\ActivityRepository;
use Esquare\Theme\Notion\NotionException;

try {
    $activities = ActivityRepository::create()->upcomingPublished(6);
} catch (NotionException $e) {
    error_log('[esquare] notion: ' . $e->getMessage());
    $activities = [];
}

foreach ($activities as $activity) {
    echo $activity->title, ' — ', $activity->start?->format('d/m/Y'), PHP_EOL;
}
```

Accès générique à n'importe quelle base :

```php
use Esquare\Theme\Notion\DatabaseRepository;

$db   = DatabaseRepository::create();
$rows = $db->rows($databaseId, sorts: [['property' => 'Date', 'direction' => 'ascending']], limit: 20);
```

## Où c'est utilisé

Le bloc dynamique `esquare/agenda` (`src/Block/Agenda.php`) consomme
`ActivityRepository::upcomingPublished()` et rend deux variantes qui partagent la
même ligne (`agenda-row.php`) :

- `{"variant":"home","limit":6}` : la bande sous « Nos événements & ateliers »
  dans `templates/front-page.html`.
- `{"variant":"page"}` : la liste complète groupée par mois, dans
  `templates/page-agenda.html` (page « Agenda », `/agenda/`).

Seul le statut `Date validée (Public)` est publié (`Activity::STATUS_PUBLISHED`).
Renommer cette valeur dans Notion vide l'agenda : la constante doit suivre.

## Test

```bash
php wp-content/themes/esquare/bin/notion-check.php            # utilise DB_ACTIVITIES
php wp-content/themes/esquare/bin/notion-check.php <db-id>    # force une autre base
```

Le script vérifie le token, liste les bases partagées, affiche le schéma, les
premières lignes et le résultat d'`ActivityRepository`.

## Version de l'API

Le client est épinglé sur `2022-06-28`, où une base se requête directement
(`POST /v1/databases/{id}/query`). À partir de `2025-09-03`, une base contient
des *data sources* et les requêtes passent par `/v1/data_sources/{id}/query` :
changer `NOTION_VERSION` impose donc d'adapter `DatabaseRepository`.
