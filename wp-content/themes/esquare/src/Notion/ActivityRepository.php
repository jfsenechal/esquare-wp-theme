<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Reads the "Activités" database (DB_ACTIVITIES in `.env`) as Activity objects.
 *
 * Results are cached through NotionCache when WP is loaded, so a page view
 * never blocks on Notion; outside WordPress (CLI scripts, tests) the cache is
 * simply skipped.
 */
final class ActivityRepository
{
    public const CACHE_TTL = 900; // 15 min — the planning changes a few times a day.

    private readonly string $databaseId;

    public function __construct(
        private readonly DatabaseRepository $databases,
        ?string $databaseId = null,
    ) {
        $id = NotionConfig::normalizeId($databaseId ?? NotionConfig::activitiesDatabaseId());
        if ($id === '') {
            throw new NotionException(
                'DB_ACTIVITIES is missing or is not a Notion database id (32 hex characters).',
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
     * Every activity, most recent first.
     *
     * @return list<Activity>
     */
    public function all(?int $limit = null): array
    {
        return $this->fetch(null, $this->sortByDate('descending'), $limit);
    }

    /**
     * Activities starting today or later, soonest first.
     *
     * @return list<Activity>
     */
    public function upcoming(?int $limit = null, ?\DateTimeImmutable $from = null): array
    {
        $from ??= new \DateTimeImmutable('today');

        return $this->fetch(
            [
                'property' => Activity::PROP_DATE,
                'date'     => ['on_or_after' => $from->format('Y-m-d')],
            ],
            $this->sortByDate('ascending'),
            $limit
        );
    }

    /**
     * Activities confirmed for publication and not yet over — what the public
     * agenda shows.
     *
     * Notion filters on the start date, so a two-day event that began yesterday
     * would drop out of an "on_or_after today" query while it is still running.
     * The window therefore opens a fortnight back and the real cutoff is applied
     * here, on the end date.
     *
     * @return list<Activity>
     */
    public function upcomingPublished(?int $limit = null, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        $activities = array_values(array_filter(
            $this->fetch(
                [
                    'and' => [
                        [
                            'property' => Activity::PROP_DATE,
                            'date'     => ['on_or_after' => $now->modify('-14 days')->format('Y-m-d')],
                        ],
                        [
                            'property' => Activity::PROP_STATUS,
                            'status'   => ['equals' => Activity::STATUS_PUBLISHED],
                        ],
                    ],
                ],
                $this->sortByDate('ascending'),
                null
            ),
            static fn (Activity $activity): bool => ! $activity->isOver($now)
        ));

        return $limit === null ? $activities : array_slice($activities, 0, $limit);
    }

    /** @return list<Activity> */
    public function between(\DateTimeImmutable $start, \DateTimeImmutable $end, ?int $limit = null): array
    {
        return $this->fetch(
            [
                'and' => [
                    ['property' => Activity::PROP_DATE, 'date' => ['on_or_after' => $start->format('Y-m-d')]],
                    ['property' => Activity::PROP_DATE, 'date' => ['on_or_before' => $end->format('Y-m-d')]],
                ],
            ],
            $this->sortByDate('ascending'),
            $limit
        );
    }

    /**
     * One activity by page id, or null when Notion does not know it. Only rows
     * of the activities database count: any other page shared with the
     * integration is treated as not found, so it cannot be shown as an activity.
     */
    public function find(string $pageId): ?Activity
    {
        $id = NotionConfig::normalizeId($pageId);
        if ($id === '') {
            return null;
        }

        $cacheKey = 'activity_' . $id;
        $page     = NotionCache::get($cacheKey);

        if ($page === null) {
            try {
                $page = $this->databases->retrievePage($id);
            } catch (NotionException $e) {
                if (! $e->isNotFound()) {
                    throw $e;
                }

                $page = [];
            }

            // Misses are cached too: a mistyped URL must not cost a Notion call per hit.
            NotionCache::set($cacheKey, $page, self::CACHE_TTL);
        }

        if ($page === [] || ! $this->belongsToDatabase($page)) {
            return null;
        }

        return Activity::fromPage($page);
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

    /**
     * Drop everything cached from Notion (lists, single activities, page
     * content), e.g. from an admin "refresh" action.
     */
    public function flush(): void
    {
        NotionCache::flush();
    }

    /**
     * @param array<string,mixed>|null       $filter
     * @param list<array<string,mixed>>|null $sorts
     * @return list<Activity>
     */
    private function fetch(?array $filter, ?array $sorts, ?int $limit): array
    {
        $cacheKey = 'activities_' . substr(md5(serialize([$this->databaseId, $filter, $sorts, $limit])), 0, 12);

        $pages = NotionCache::get($cacheKey);
        if ($pages === null) {
            $pages = $this->databases->query($this->databaseId, $filter, $sorts, $limit);
            NotionCache::set($cacheKey, $pages, self::CACHE_TTL);
        }

        return array_map(Activity::fromPage(...), $pages);
    }

    /** @param array<string,mixed> $page */
    private function belongsToDatabase(array $page): bool
    {
        $parent = is_array($page['parent'] ?? null) ? $page['parent'] : [];

        return NotionConfig::normalizeId((string) ($parent['database_id'] ?? '')) === $this->databaseId;
    }

    /** @return list<array<string,mixed>> */
    private function sortByDate(string $direction): array
    {
        return [['property' => Activity::PROP_DATE, 'direction' => $direction]];
    }
}
