<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Reads the body of a Notion page: its blocks, as opposed to its properties.
 *
 * The API only ever returns one level of children per call, so a nested block
 * (column, callout, list item…) costs one more request. tree() walks a whole
 * subtree; callers that only need part of a page should locate it with
 * children() first and expand just that block, as ActivityContent does.
 */
final class BlockRepository
{
    /** Hard stop for tree(): Notion pages nest deep, the site never needs to. */
    public const MAX_DEPTH = 6;

    /**
     * Blocks whose children are separate pages or databases, not part of the
     * body — expanding them would pull internal wiki / task content in.
     */
    private const OPAQUE = ['child_page', 'child_database', 'link_to_page'];

    public function __construct(private readonly NotionClient $client)
    {
    }

    public static function create(): self
    {
        return new self(NotionClient::fromEnv());
    }

    /**
     * Direct children of a page or block, raw API objects, in display order.
     *
     * @return list<array<string,mixed>>
     */
    public function children(string $blockId): array
    {
        return iterator_to_array(
            $this->client->paginate('GET', '/blocks/' . $this->id($blockId) . '/children'),
            false
        );
    }

    /**
     * Children of a block with their own descendants attached under the
     * `children` key of each block, down to $maxDepth levels.
     *
     * @return list<array<string,mixed>>
     */
    public function tree(string $blockId, int $maxDepth = self::MAX_DEPTH): array
    {
        return $this->expand($this->children($blockId), $maxDepth - 1);
    }

    /**
     * Attach descendants to blocks already fetched with children().
     *
     * @param list<array<string,mixed>> $blocks
     * @return list<array<string,mixed>>
     */
    public function expand(array $blocks, int $maxDepth = self::MAX_DEPTH): array
    {
        foreach ($blocks as $i => $block) {
            $blocks[$i]['children'] = $maxDepth > 0 && self::hasExpandableChildren($block)
                ? $this->tree((string) $block['id'], $maxDepth)
                : [];
        }

        return $blocks;
    }

    /** @param array<string,mixed> $block */
    public static function hasExpandableChildren(array $block): bool
    {
        return ($block['has_children'] ?? false) === true
            && isset($block['id'])
            && ! in_array($block['type'] ?? '', self::OPAQUE, true);
    }

    /**
     * Plain text of a block's own rich text (not its children's).
     *
     * @param array<string,mixed> $block
     */
    public static function text(array $block): string
    {
        $payload = $block[(string) ($block['type'] ?? '')] ?? null;

        return is_array($payload) && is_array($payload['rich_text'] ?? null)
            ? PageNormalizer::plainText($payload['rich_text'])
            : '';
    }

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
