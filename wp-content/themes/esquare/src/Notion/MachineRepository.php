<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Reads the FabLab "Machines" database (DB_MATERIALS in `.env`) as Machine objects.
 *
 * Cached through NotionCache like the activities; 15 minutes also keeps the
 * Notion-hosted image URLs, which expire after an hour, valid in the page.
 */
final class MachineRepository
{
    public const CACHE_TTL = 900;

    private readonly string $databaseId;

    public function __construct(
        private readonly DatabaseRepository $databases,
        ?string $databaseId = null,
    ) {
        $id = NotionConfig::normalizeId($databaseId ?? NotionConfig::materialsDatabaseId());
        if ($id === '') {
            throw new NotionException(
                'DB_MATERIALS is missing or is not a Notion database id (32 hex characters).',
                0,
                'validation_error'
            );
        }

        $this->databaseId = $id;
    }

    public static function create(?string $databaseId = null): self
    {
        return new self(DatabaseRepository::create(), $databaseId);
    }

    /**
     * Every machine meant for the public site, alphabetically.
     *
     * @return list<Machine>
     */
    public function published(): array
    {
        $cacheKey = 'machines_' . $this->databaseId;

        $pages = NotionCache::get($cacheKey);
        if ($pages === null) {
            $pages = $this->databases->query(
                $this->databaseId,
                sorts: [['property' => Machine::PROP_TITLE, 'direction' => 'ascending']]
            );
            NotionCache::set($cacheKey, $pages, self::CACHE_TTL);
        }

        return array_values(array_filter(
            array_map(Machine::fromPage(...), $pages),
            static fn (Machine $machine): bool => $machine->isPublic()
        ));
    }

    /** Property name => Notion type, straight from the live database. */
    public function schema(): array
    {
        return $this->databases->schema($this->databaseId);
    }

    public function databaseId(): string
    {
        return $this->databaseId;
    }
}
