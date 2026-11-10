<?php

declare(strict_types=1);

namespace Esquare\Theme\Block;

/**
 * French typography for the theme's own blocks (esquare/*).
 *
 * Block attributes are stored with escaped apostrophes (&#039;), which
 * wptexturize() leaves alone, so block text kept straight quotes where the
 * old hand-written HTML got curly ones. On each esquare/* block's output,
 * and only in visible text (never in tags, attributes, scripts, styles or
 * form fields), this:
 *  - turns escaped apostrophes back into ' and runs wptexturize() (’ “ ” …);
 *  - puts a no-break space before ? ! ; : » and after «.
 */
final class Typography
{
    /** Elements whose content is not prose and must stay byte-for-byte. */
    private const RAW_ELEMENTS = 'script|style|textarea|pre|code|svg';

    public static function register(): void
    {
        add_filter('render_block', [self::class, 'filter'], 10, 2);
    }

    /**
     * @param array<string, mixed> $block
     */
    public static function filter(string $content, array $block): string
    {
        $name = (string) ($block['blockName'] ?? '');
        if ($content === '' || ! str_starts_with($name, 'esquare/')) {
            return $content;
        }

        return self::apply($content);
    }

    public static function apply(string $html): string
    {
        $parts = preg_split(
            '#(<(?:' . self::RAW_ELEMENTS . ')\b.*?</(?:' . self::RAW_ELEMENTS . ')>|<!--.*?-->|<[^>]*>)#is',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if ($parts === false) {
            return $html;
        }

        foreach ($parts as $i => $part) {
            // Odd indexes are the captured tags, comments and raw elements.
            if ($i % 2 === 1 || trim($part) === '') {
                continue;
            }
            $text = str_replace(['&#039;', '&#39;', '&apos;'], "'", $part);
            // A plain space (or none after «) becomes a no-break space.
            $text = preg_replace('/ (?=[?!;:»])/u', "\u{00A0}", $text) ?? $text;
            $text = preg_replace('/«(?: |(?!\x{00A0}|&nbsp;))/u', "«\u{00A0}", $text) ?? $text;
            $parts[$i] = $text;
        }

        // wptexturize() skips tags, script, style, pre and code by itself.
        return wptexturize(implode('', $parts));
    }
}
