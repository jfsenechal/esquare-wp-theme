<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Reads the "Activités" database (DB_ACTIVITIES in `.env`) as Activity objects.
 *
 * Results are cached in a WordPress transient when WP is loaded, so a page view
 * never blocks on Notion; outside WordPress (CLI scripts, tests) the cache is
 * simply skipped.
 */
final class ActivityRepository
{
    public const CACHE_KEY = 'esquare_notion_activities';
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

    public function find(string $pageId): ?Activity
    {
        try {
            return Activity::fromPage($this->databases->retrievePage($pageId));
        } catch (NotionException $e) {
            if ($e->isNotFound()) {
                return null;
            }

            throw $e;
        }
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

    /** Drop the cached rows, e.g. from an admin "refresh" action. */
    public function flush(): void
    {
        if (function_exists('delete_transient')) {
            delete_transient(self::CACHE_KEY);
        }
    }

    /**
     * @param array<string,mixed>|null       $filter
     * @param list<array<string,mixed>>|null $sorts
     * @return list<Activity>
     */
    private function fetch(?array $filter, ?array $sorts, ?int $limit): array
    {
        $cacheKey = self::CACHE_KEY . '_' . substr(md5(serialize([$this->databaseId, $filter, $sorts, $limit])), 0, 12);

        $cached = $this->cacheGet($cacheKey);
        if ($cached !== null) {
            return array_map(Activity::fromPage(...), $cached);
        }

        $pages = $this->databases->query($this->databaseId, $filter, $sorts, $limit);
        $this->cacheSet($cacheKey, $pages);

        return array_map(Activity::fromPage(...), $pages);
    }

    /** @return list<array<string,mixed>>|null */
    private function cacheGet(string $key): ?array
    {
        if (! function_exists('get_transient')) {
            return null;
        }

        $cached = get_transient($key);

        return is_array($cached) ? $cached : null;
    }

    /** @param list<array<string,mixed>> $pages */
    private function cacheSet(string $key, array $pages): void
    {
        if (function_exists('set_transient')) {
            set_transient($key, $pages, self::CACHE_TTL);
        }
    }

    /** @return list<array<string,mixed>> */
    private function sortByDate(string $direction): array
    {
        return [['property' => Activity::PROP_DATE, 'direction' => $direction]];
    }
}
