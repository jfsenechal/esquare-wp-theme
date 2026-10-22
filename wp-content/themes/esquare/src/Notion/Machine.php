<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * One row of the FabLab "Machines" Notion database (DB_MATERIALS in `.env`).
 *
 * Property names are the labels used in Notion; renaming a column there only
 * requires changing the matching constant here.
 */
final class Machine
{
    public const PROP_TITLE        = 'Articles';
    public const PROP_DESCRIPTION  = 'Description';
    public const PROP_IMAGES       = 'Image';
    public const PROP_PRICE        = 'Prix tvac';
    public const PROP_UNIT         = 'Unités';
    public const PROP_SUBSCRIPTION = 'Abonnement';
    public const PROP_BOOKING      = 'Réserver';

    /**
     * Rows of the base that are services, not machines, and must stay off the
     * public list. The base has no "published" column; adding a checkbox in
     * Notion would make this list unnecessary.
     */
    public const EXCLUDED_TITLES = ['Location du FabLab'];

    /**
     * @param list<string>        $images
     * @param array<string,mixed> $properties Every normalized property, for anything not mapped below.
     */
    private function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly array $images,
        public readonly ?float $price,
        public readonly ?string $unit,
        public readonly string $subscription,
        public readonly ?string $bookingUrl,
        public readonly array $properties,
    ) {
    }

    /** @param array<string,mixed> $page Raw Notion page object. */
    public static function fromPage(array $page): self
    {
        $row   = PageNormalizer::normalize($page);
        $props = $row['properties'];

        $price = $props[self::PROP_PRICE] ?? null;
        $images = is_array($props[self::PROP_IMAGES] ?? null) ? $props[self::PROP_IMAGES] : [];

        return new self(
            id: $row['id'],
            // Titles are typed by hand in Notion: stray line breaks and trailing spaces.
            title: self::squish((string) ($props[self::PROP_TITLE] ?? '')),
            description: trim((string) ($props[self::PROP_DESCRIPTION] ?? '')),
            images: array_values(array_filter($images, 'is_string')),
            price: is_numeric($price) && (float) $price > 0 ? (float) $price : null,
            unit: is_string($props[self::PROP_UNIT] ?? null) ? $props[self::PROP_UNIT] : null,
            subscription: self::squish((string) ($props[self::PROP_SUBSCRIPTION] ?? '')),
            bookingUrl: is_string($props[self::PROP_BOOKING] ?? null) && $props[self::PROP_BOOKING] !== ''
                ? $props[self::PROP_BOOKING]
                : null,
            properties: $props,
        );
    }

    /** Whether the row may be shown on the public machine list. */
    public function isPublic(): bool
    {
        return $this->title !== '' && ! in_array($this->title, self::EXCLUDED_TITLES, true);
    }

    public function coverImage(): ?string
    {
        return $this->images[0] ?? null;
    }

    /**
     * Price line for the public site, e.g. "0,50 € TVAC / minute", or the
     * subscription note ("Compris dans abonnement") for machines without a
     * usage fee. Empty when Notion holds neither.
     */
    public function priceLabel(): string
    {
        if ($this->price !== null) {
            $label = number_format($this->price, 2, ',', ' ') . ' € TVAC';

            return $this->unit !== null ? $label . ' / ' . $this->unit : $label;
        }

        return $this->subscription;
    }

    /** Plain array for Twig / wp_json_encode. */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'images'       => $this->images,
            'price'        => $this->price,
            'unit'         => $this->unit,
            'subscription' => $this->subscription,
            'booking_url'  => $this->bookingUrl,
            'price_label'  => $this->priceLabel(),
        ];
    }

    private static function squish(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
