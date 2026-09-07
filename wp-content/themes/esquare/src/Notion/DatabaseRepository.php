<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Database-shaped reads on top of NotionClient: schema, rows, and discovery.
 *
 * Everything here speaks the 2022-06-28 shape (a database is queried directly).
 * If NOTION_VERSION is moved to 2025-09-03 or later, queries have to target
 * /v1/data_sources/{id}/query instead — see NotionClient::DEFAULT_VERSION.
 */
final class DatabaseRepository
{
    public function __construct(private readonly NotionClient $client)
    {
    }

    public static function create(): self
    {
        return new self(NotionClient::fromEnv());
    }

    /**
     * Database metadata (title + property schema).
     *
     * @return array<string,mixed>
     */
    public function retrieve(string $databaseId): array
    {
        return $this->client->get('/databases/' . $this->id($databaseId));
    }

    /** Human-readable title of a database, for logs and admin screens. */
    public function title(string $databaseId): string
    {
        $database = $this->retrieve($databaseId);

        return PageNormalizer::plainText(is_array($database['title'] ?? null) ? $database['title'] : []);
    }

    /**
     * Property names mapped to their Notion type, e.g. ['Nom' => 'title'].
     *
     * @return array<string,string>
     */
    public function schema(string $databaseId): array
    {
        $schema = [];
        foreach (($this->retrieve($databaseId)['properties'] ?? []) as $name => $property) {
            $schema[(string) $name] = (string) ($property['type'] ?? 'unknown');
        }

        return $schema;
    }

    /**
     * Raw pages of a database, following pagination.
     *
     * @param array<string,mixed>|null       $filter Notion filter object.
     * @param list<array<string,mixed>>|null $sorts  Notion sort objects.
     * @return list<array<string,mixed>>
     */
    public function query(string $databaseId, ?array $filter = null, ?array $sorts = null, ?int $limit = null): array
    {
        $payload = [];
        if ($filter !== null && $filter !== []) {
            $payload['filter'] = $filter;
        }
        if ($sorts !== null && $sorts !== []) {
            $payload['sorts'] = $sorts;
        }

        return iterator_to_array(
            $this->client->paginate('POST', '/databases/' . $this->id($databaseId) . '/query', $payload, $limit),
            false
        );
    }

    /**
     * Same as query(), flattened by PageNormalizer — the form a template wants.
     *
     * @param array<string,mixed>|null       $filter
     * @param list<array<string,mixed>>|null $sorts
     * @return list<array<string,mixed>>
     */
    public function rows(string $databaseId, ?array $filter = null, ?array $sorts = null, ?int $limit = null): array
    {
        return PageNormalizer::normalizeAll($this->query($databaseId, $filter, $sorts, $limit));
    }

    /**
     * One page (a database row, or any page shared with the integration).
     *
     * @return array<string,mixed>
     */
    public function retrievePage(string $pageId): array
    {
        return $this->client->get('/pages/' . $this->id($pageId));
    }

    /**
     * Every database the integration has been granted access to.
     *
     * The workspace owner has to share each database with the integration
     * explicitly; an empty list almost always means "nothing shared yet", not
     * "wrong token". Handy to recover a database id you don't have at hand.
     *
     * @return list<array{id:string,title:string,url:string}>
     */
    public function listDatabases(?string $search = null, int $limit = 50): array
    {
        $payload = ['filter' => ['value' => 'database', 'property' => 'object']];
        if ($search !== null && $search !== '') {
            $payload['query'] = $search;
        }

        $databases = [];
        foreach ($this->client->paginate('POST', '/search', $payload, $limit) as $database) {
            $databases[] = [
                'id'    => (string) ($database['id'] ?? ''),
                'title' => PageNormalizer::plainText(is_array($database['title'] ?? null) ? $database['title'] : []),
                'url'   => (string) ($database['url'] ?? ''),
            ];
        }

        return $databases;
    }

    /**
     * @throws NotionException when the value is not a Notion id at all — caught
     *         here rather than as a confusing 400 from the API.
     */
    private function id(string $value): string
    {
        $id = NotionConfig::normalizeId($value);
        if ($id === '') {
            throw new NotionException(
                sprintf('"%s" is not a Notion id (expected 32 hex characters, dashed or not).', $value),
                0,
                'validation_error'
            );
        }

        return $id;
    }
}
