<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * The public body of an activity page, as HTML.
 *
 * Activity pages are built from a project-tracking template: most of the page
 * (project description, objectives, wiki, tasks) is internal. Only the children
 * of the callout titled "DESCRIPTION POUR SITE WEB" are meant for the site, so
 * that is all this class ever reads. When the section is missing, or still holds
 * the template's placeholder text, the "Description publique" property is used.
 *
 * Cached per page through NotionCache; ActivityRepository::flush() clears it too.
 */
final class ActivityContent
{
    /** Start of the callout's own text, compared case-insensitively. */
    public const SECTION_LABEL = 'DESCRIPTION POUR SITE WEB';

    /**
     * Lines the Notion template ships inside the section. Seeing only these
     * means nobody wrote the public text yet.
     */
    public const PLACEHOLDERS = [
        "Explication de l'événement (contenu, programme)",
        'Vous pouvez ajouter des émojis et mettre en page (liste à puces…)',
    ];

    public const CACHE_KEY = 'content_';
    /** Must stay well under an hour: Notion-hosted image URLs expire after that. */
    public const CACHE_TTL = 900;

    /** The section is a top-level block, or inside columns; never deeper than this. */
    private const SEARCH_DEPTH = 3;

    /** Containers worth descending into while looking for the section. */
    private const SEARCH_INTO = ['column_list', 'column', 'toggle', 'synced_block'];

    public function __construct(
        private readonly BlockRepository $blocks,
        private readonly BlockRenderer $renderer = new BlockRenderer(),
    ) {
    }

    public static function create(): self
    {
        return new self(BlockRepository::create());
    }

    /**
     * HTML for the activity's detail page; empty string when there is nothing
     * public to show.
     *
     * @throws NotionException when Notion cannot be reached.
     */
    public function html(Activity $activity): string
    {
        $html = $this->renderer->render($this->section($activity->id));
        if ($html !== '') {
            return $html;
        }

        return self::descriptionHtml($activity);
    }

    /** The "Description publique" property as HTML, one paragraph per blank-line block. */
    public static function descriptionHtml(Activity $activity): string
    {
        $paragraphs = preg_split('/\R{2,}/u', trim($activity->description)) ?: [];

        return implode('', array_map(
            static fn (string $p): string => '<p>' . nl2br(htmlspecialchars(trim($p), ENT_QUOTES | ENT_HTML5, 'UTF-8'), false) . '</p>',
            array_filter($paragraphs, static fn (string $p): bool => trim($p) !== '')
        ));
    }

    /**
     * Block tree of the public section, placeholders removed. Empty when the
     * page has no such section or it holds nothing but the template text.
     *
     * @return list<array<string,mixed>>
     */
    public function section(string $pageId): array
    {
        $key    = self::CACHE_KEY . NotionConfig::normalizeId($pageId);
        $cached = NotionCache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $callout = $this->findSection($this->blocks->children($pageId), self::SEARCH_DEPTH);
        $section = $callout === null ? [] : $this->withoutPlaceholders($this->blocks->tree((string) $callout['id']));

        NotionCache::set($key, $section, self::CACHE_TTL);

        return $section;
    }

    /**
     * Breadth-first: top-level blocks are checked before any column is opened,
     * so the usual layout costs a single request.
     *
     * @param list<array<string,mixed>> $blocks
     * @return array<string,mixed>|null
     */
    private function findSection(array $blocks, int $depth): ?array
    {
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'callout' && $this->isSectionLabel(BlockRepository::text($block))) {
                return ($block['has_children'] ?? false) === true ? $block : null;
            }
        }

        if ($depth <= 1) {
            return null;
        }

        foreach ($blocks as $block) {
            if (in_array($block['type'] ?? '', self::SEARCH_INTO, true) && BlockRepository::hasExpandableChildren($block)) {
                $found = $this->findSection($this->blocks->children((string) $block['id']), $depth - 1);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function isSectionLabel(string $text): bool
    {
        return str_starts_with(mb_strtoupper(trim($text)), self::SECTION_LABEL);
    }

    /**
     * @param list<array<string,mixed>> $blocks
     * @return list<array<string,mixed>>
     */
    private function withoutPlaceholders(array $blocks): array
    {
        $placeholders = array_map($this->comparable(...), self::PLACEHOLDERS);

        $kept = array_values(array_filter(
            $blocks,
            fn (array $block): bool => ! in_array($this->comparable(BlockRepository::text($block)), $placeholders, true)
        ));

        // Only empty paragraphs and dividers left: nothing was actually written.
        foreach ($kept as $block) {
            if (BlockRepository::text($block) !== '' || ($block['children'] ?? []) !== [] || ($block['type'] ?? '') === 'image') {
                return $kept;
            }
        }

        return [];
    }

    /** Notion swaps ' for ’ and ... for … as editors type. */
    private function comparable(string $text): string
    {
        $text = str_replace(['’', '‘', '...'], ["'", "'", '…'], $text);

        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
    }
}
