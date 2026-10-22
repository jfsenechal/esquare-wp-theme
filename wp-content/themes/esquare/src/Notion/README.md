# Notion

Client REST Notion pour le thème e-Square.

## Configuration (`.env`)

```dotenv
NOTION_API_KEY=secret_…          # ou ntn_… (token d'intégration interne)
DB_ACTIVITIES=7282d0db-d353-4923-b78d-f4d48b9ca02b   # id de la base "Activités"
DB_MATERIALS=d3c2fd9fd68d434b8946d29b36281775        # id de la base "Machines" (FabLab)
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
| `Activity` / `ActivityRepository` | la base "Activités", typée et mise en cache (transient, 15 min) ; `flush()` vide tout le cache Notion |
| `Machine` / `MachineRepository` | la base "Machines" du FabLab (`DB_MATERIALS`), typée et mise en cache 15 min |
| `BlockRepository` | contenu d'une page (blocs), un niveau ou un sous-arbre |
| `BlockRenderer` | blocs → HTML sûr (liste blanche de types, texte échappé) |
| `ActivityContent` | corps public d'une activité (section « DESCRIPTION POUR SITE WEB »), mis en cache 15 min |
| `NotionCache` | transients partagés par toutes les lectures ; `flush()` vide tout d'un coup (compteur de génération) |
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

## Contenu d'une activité

Les pages d'activité suivent un modèle « suivi de projet » dont presque tout est
interne. Seuls les enfants du callout **« DESCRIPTION POUR SITE WEB »** sont
publiés (`ActivityContent::SECTION_LABEL`) : le reste (description du projet,
objectifs, wiki, tâches, sous-pages, bases) n'est jamais lu.

```php
use Esquare\Theme\Notion\ActivityContent;

$html = ActivityContent::create()->html($activity); // HTML sûr, ou ''
```

- Le texte d'exemple du modèle (`ActivityContent::PLACEHOLDERS`) est ignoré ;
  une section qui ne contient que lui compte comme vide.
- Section absente ou vide → repli sur la propriété `Description publique`.
- Titres décalés d'un niveau (h1 Notion → `<h2>`, le `<h1>` est le titre de la page), le gras des titres est
  retiré, les liens vers notion.so sont supprimés (le texte reste).
- Rendus : paragraphe, titres, listes, citation, callout, toggle, séparateur,
  image, signet. Le reste (tableaux, code, sous-pages, bases…) est ignoré.
- Cache : `NotionCache`, 15 min — à garder sous l'heure, durée de validité des
  URLs d'images hébergées par Notion. `ActivityRepository::flush()` le vide aussi.

Aperçu en CLI (sans cache) :

```bash
php wp-content/themes/esquare/bin/notion-content.php <page-id-ou-url-notion>
```

## Où c'est utilisé

Le bloc dynamique `esquare/agenda` (`src/Block/Agenda.php`) consomme
`ActivityRepository::upcomingPublished()` et rend deux variantes qui partagent la
même ligne (`agenda-row.php`) :

- `{"variant":"home","limit":6}` : la bande sous « Nos événements & ateliers »
  dans `templates/front-page.html`.
- `{"variant":"page"}` : la liste complète groupée par mois, dans
  `templates/page-agenda.html` (page « Agenda », `/agenda/`).

Chaque ligne renvoie à sa page de détail, `/agenda/<slug>-<id notion>/`
(`src/Block/ActivityDetail.php`, bloc `esquare/activity`, gabarit
`templates/activity.html`). Pas d'article WordPress derrière : une règle de
réécriture pointe sur la page « agenda » avec l'id en query var. L'id seul
identifie l'activité ; un slug périmé redirige en 301, une activité inconnue ou
non publique renvoie 404, Notion injoignable renvoie 503. La règle est vidée
automatiquement au déploiement (`ActivityDetail::REWRITE_VERSION`).

Le bloc dynamique `esquare/machines` (`src/Block/Machines.php`, gabarit
`machines-template.php`) rend toute la section « Le parc de machines »
(`#equipements`) de la page Fablab : il est inséré dans le contenu de la page,
entre deux blocs HTML. Les machines avec une `Image` dans Notion deviennent des
cartes (prix TVAC / unité ou mention d'abonnement, description, lien
`Réserver`), les autres vont dans « Et aussi au FabLab ». Ajouter une photo dans
Notion fait donc passer une machine en carte. Les lignes qui ne sont pas des
machines sont écartées par `Machine::EXCLUDED_TITLES`.

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
