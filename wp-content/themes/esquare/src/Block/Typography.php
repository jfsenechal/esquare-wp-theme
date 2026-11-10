<?php

declare(strict_types=1);

namespace Esquare\Theme\Block;

/**
 * French typography for everything the site renders through blocks:
 * templates, template parts, page and post content.
 *
 * Block attributes are stored with escaped apostrophes (&#039;), which
 * wptexturize() leaves alone, so block text kept straight quotes where the
 * old hand-written HTML got curly ones; and nothing adds the no-break spaces
 * French punctuation needs. On each top-level block's output, and only in
 * visible text (never in tags, attributes, scripts, styles or form fields),
 * this:
 *  - turns escaped apostrophes back into ' and runs wptexturize() (’ “ ” …);
 *  - puts a no-break space before ? ! ; : » and after «.
 */
final class Typography
{
    /** Elements whose content is not prose and must stay byte-for-byte. */
    private const RAW_ELEMENTS = 'script|style|textarea|pre|code|svg';

    /** Set on inner blocks: their parent's output already includes them. */
    private const INNER_FLAG = 'esquareInnerBlock';

    public static function register(): void
    {
        add_filter('render_block_data', [self::class, 'flagInnerBlock'], 10, 3);
        add_filter('render_block', [self::class, 'filter'], 10, 2);
        // The <title> tag: core already runs wptexturize() on it.
        add_filter('document_title', [self::class, 'spaces']);
    }

    /**
     * @param array<string, mixed> $parsedBlock
     * @param array<string, mixed> $sourceBlock
     *
     * @return array<string, mixed>
     */
    public static function flagInnerBlock(array $parsedBlock, array $sourceBlock, ?\WP_Block $parentBlock = null): array
    {
        if ($parentBlock !== null) {
            $parsedBlock[self::INNER_FLAG] = true;
        }

        return $parsedBlock;
    }

    /**
     * Only top-level blocks are processed, so each piece of text is handled
     * once per document. (Post content is a document of its own inside the
     * template; apply() is idempotent, so a second pass changes nothing.)
     *
     * @param array<string, mixed> $block
     */
    public static function filter(string $content, array $block): string
    {
        if ($content === '' || ! empty($block[self::INNER_FLAG])) {
            return $content;
        }

        return self::apply($content);
    }

    public static function apply(string $html): string
    {
        // Set raw elements aside: wptexturize() would read the "<" of a
        // script's comparisons as tags and turn its "&&" into "&#038;&#038;".
        $raw  = [];
        $html = preg_replace_callback(
            '#<(' . self::RAW_ELEMENTS . ')\b.*?</\1>#is',
            static function (array $m) use (&$raw): string {
                $raw[] = $m[0];

                return '<esquare-raw-' . (count($raw) - 1) . '>';
            },
            $html
        ) ?? $html;

        $parts = preg_split('#(<!--.*?-->|<[^>]*>)#s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return self::restore($html, $raw);
        }
        foreach ($parts as $i => $part) {
            // Odd indexes are the captured tags and comments.
            if ($i % 2 === 1 || trim($part) === '') {
                continue;
            }
            $parts[$i] = self::spaces(str_replace(['&#039;', '&#39;', '&apos;'], "'", $part));
        }

        // One pass over the whole document keeps the context wptexturize()
        // needs to tell opening from closing quotes; it skips tags itself.
        return self::restore(wptexturize(implode('', $parts)), $raw);
    }

    /**
     * A plain space before ? ! ; : » (or none after «) becomes a no-break
     * space. Expects text without tags.
     */
    public static function spaces(string $text): string
    {
        $text = preg_replace('/ (?=[?!;:»])/u', "\u{00A0}", $text) ?? $text;

        return preg_replace('/«(?: |(?!\x{00A0}|&nbsp;))/u', "«\u{00A0}", $text) ?? $text;
    }

    /**
     * @param list<string> $raw
     */
    private static function restore(string $html, array $raw): string
    {
        return preg_replace_callback(
            '#<esquare-raw-(\d+)>#',
            static fn (array $m): string => $raw[(int) $m[1]] ?? $m[0],
            $html
        ) ?? $html;
    }
}
