<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * One row of the "Activités" Notion database, in the shape the theme needs.
 *
 * Property names are the French labels used in Notion; renaming a column there
 * only requires changing the matching constant here.
 */
final class Activity
{
    public const PROP_TITLE        = 'Activité';
    public const PROP_DESCRIPTION  = 'Description publique';
    public const PROP_DATE         = 'Date';
    public const PROP_STATUS       = 'Statut';
    public const PROP_CATEGORIES   = 'Catégorie';
    public const PROP_THEME        = 'Thématique';
    public const PROP_AUDIENCES    = 'Public cible';
    public const PROP_ORGANISERS   = 'Entité organisatrice';
    public const PROP_REGISTRATION = 'Lien inscription';
    public const PROP_MAX_PEOPLE   = 'Nb.Max.participants';
    public const PROP_IMAGES       = "Photo illustrant l'activité";
    public const PROP_DURATION     = 'Durée';
    public const PROP_IDENTIFIER   = 'Identifiant';
    public const PROP_ROOMS        = 'Salles';

    /**
     * The one status that means "confirmed, and meant for the public site".
     *
     * The workspace also uses "Date validée (interne)" for dates that are locked
     * but internal, plus "Date à valider" / "Date proposée" while scheduling is
     * still moving. Only the value below may be published.
     */
    public const STATUS_PUBLISHED = 'Date validée (Public)';

    /**
     * @param list<string> $categories
     * @param list<string> $audiences
     * @param list<string> $organisers
     * @param list<string> $images
     * @param list<string> $roomIds
     * @param array<string,mixed> $properties Every normalized property, for anything not mapped below.
     */
    private function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly string $identifier,
        public readonly string $title,
        public readonly string $description,
        public readonly ?\DateTimeImmutable $start,
        public readonly ?\DateTimeImmutable $end,
        public readonly bool $allDay,
        public readonly string $status,
        public readonly array $categories,
        public readonly ?string $theme,
        public readonly array $audiences,
        public readonly array $organisers,
        public readonly ?string $registrationUrl,
        public readonly ?int $maxParticipants,
        public readonly array $images,
        public readonly ?string $duration,
        public readonly array $roomIds,
        public readonly array $properties,
    ) {
    }

    /** @param array<string,mixed> $page Raw Notion page object. */
    public static function fromPage(array $page): self
    {
        $row   = PageNormalizer::normalize($page);
        $props = $row['properties'];

        $date  = is_array($props[self::PROP_DATE] ?? null) ? $props[self::PROP_DATE] : [];
        $start = self::toDate($date['start'] ?? null);
        $end   = self::toDate($date['end'] ?? null);

        return new self(
            id: $row['id'],
            url: $row['url'],
            identifier: (string) ($props[self::PROP_IDENTIFIER] ?? ''),
            title: (string) ($props[self::PROP_TITLE] ?? ''),
            description: (string) ($props[self::PROP_DESCRIPTION] ?? ''),
            start: $start,
            // A date-only value ("2026-10-03") means an all-day activity.
            end: $end,
            allDay: is_string($date['start'] ?? null) && ! str_contains((string) $date['start'], 'T'),
            status: (string) ($props[self::PROP_STATUS] ?? ''),
            categories: self::toList($props[self::PROP_CATEGORIES] ?? []),
            theme: self::toNullableString($props[self::PROP_THEME] ?? null),
            audiences: self::toList($props[self::PROP_AUDIENCES] ?? []),
            organisers: self::toList($props[self::PROP_ORGANISERS] ?? []),
            registrationUrl: self::toNullableString($props[self::PROP_REGISTRATION] ?? null),
            maxParticipants: isset($props[self::PROP_MAX_PEOPLE]) && is_numeric($props[self::PROP_MAX_PEOPLE])
                ? (int) $props[self::PROP_MAX_PEOPLE]
                : null,
            images: self::toList($props[self::PROP_IMAGES] ?? []),
            duration: self::toNullableString($props[self::PROP_DURATION] ?? null),
            roomIds: self::toList($props[self::PROP_ROOMS] ?? []),
            properties: $props,
        );
    }

    /** Whether the row may be shown on the public site. */
    public function isPublic(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** True when the activity runs over more than one calendar day. */
    public function isMultiDay(): bool
    {
        return $this->start !== null
            && $this->end !== null
            && $this->end->format('Y-m-d') !== $this->start->format('Y-m-d');
    }

    /**
     * An activity is over once its last day is over, not once its start time has
     * passed: a 9am workshop still belongs on today's agenda at 11am, and a
     * two-day event stays listed on its second day.
     */
    public function isOver(?\DateTimeImmutable $now = null): bool
    {
        $lastDay = $this->end ?? $this->start;

        return $lastDay !== null && $lastDay->setTime(23, 59, 59) < ($now ?? new \DateTimeImmutable());
    }

    public function coverImage(): ?string
    {
        return $this->images[0] ?? null;
    }

    /** Plain array for Twig / wp_json_encode. */
    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'url'              => $this->url,
            'identifier'       => $this->identifier,
            'title'            => $this->title,
            'description'      => $this->description,
            'start'            => $this->start?->format(\DATE_ATOM),
            'end'              => $this->end?->format(\DATE_ATOM),
            'all_day'          => $this->allDay,
            'status'           => $this->status,
            'is_public'        => $this->isPublic(),
            'categories'       => $this->categories,
            'theme'            => $this->theme,
            'audiences'        => $this->audiences,
            'organisers'       => $this->organisers,
            'registration_url' => $this->registrationUrl,
            'max_participants' => $this->maxParticipants,
            'images'           => $this->images,
            'duration'         => $this->duration,
            'room_ids'         => $this->roomIds,
        ];
    }

    private static function toDate(mixed $value): ?\DateTimeImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    /** @return list<string> */
    private static function toList(mixed $value): array
    {
        if (! is_array($value)) {
            return $value === null || $value === '' ? [] : [(string) $value];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => is_scalar($item) ? (string) $item : '',
            $value
        ), static fn (string $item): bool => $item !== ''));
    }

    private static function toNullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
