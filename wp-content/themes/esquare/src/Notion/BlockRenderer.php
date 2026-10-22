<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Turns a Notion block tree (see BlockRepository::tree()) into safe HTML.
 *
 * Only an allow-list of block types is rendered; anything else (tables, code,
 * child pages, databases, embeds…) is dropped silently, so editors can keep
 * internal material next to the public text without it leaking. All text is
 * escaped and links are limited to http(s) / mailto / tel.
 *
 * Plain PHP on purpose (no esc_html) so the CLI preview can use it too.
 */
final class BlockRenderer
{
    /**
     * @param int $headingOffset Notion heading_1 becomes h(1 + offset). The page
     *                           around the content owns the h1, so by default
     *                           Notion's h1 becomes an h2.
     */
    public function __construct(private readonly int $headingOffset = 1)
    {
    }

    /** @param list<array<string,mixed>> $blocks */
    public function render(array $blocks): string
    {
        $html = '';
        $list = null;   // 'ul' | 'ol' while consecutive list items are open
        $items = '';

        foreach ($blocks as $block) {
            $type = (string) ($block['type'] ?? '');
            $tag  = match ($type) {
                'bulleted_list_item' => 'ul',
                'numbered_list_item' => 'ol',
                default              => null,
            };

            // Notion has no list container: consecutive items form one list.
            if ($list !== null && $tag !== $list) {
                $html .= "<{$list}>{$items}</{$list}>";
                $list = null;
                $items = '';
            }

            if ($tag !== null) {
                $list = $tag;
                $items .= '<li>' . $this->richText($block) . $this->render($block['children'] ?? []) . '</li>';
                continue;
            }

            $html .= $this->block($type, $block);
        }

        if ($list !== null) {
            $html .= "<{$list}>{$items}</{$list}>";
        }

        return $html;
    }

    /** @param array<string,mixed> $block */
    private function block(string $type, array $block): string
    {
        $children = $this->render($block['children'] ?? []);

        return match ($type) {
            'paragraph' => $this->paragraph($block, $children),
            'heading_1', 'heading_2', 'heading_3' => $this->heading((int) substr($type, -1), $block, $children),
            'quote'   => '<blockquote><p>' . $this->richText($block) . '</p>' . $children . '</blockquote>',
            'callout' => '<div class="notion-callout"><p>' . $this->richText($block) . '</p>' . $children . '</div>',
            'toggle'  => '<details><summary>' . $this->richText($block) . '</summary>' . $children . '</details>',
            'divider' => '<hr>',
            'image'   => $this->image($block),
            'bookmark', 'link_preview', 'embed' => $this->link($block[$type]['url'] ?? null),
            // Layout only: keep the content, drop the columns.
            'column_list', 'column', 'synced_block' => $children,
            default => '',
        };
    }

    /** @param array<string,mixed> $block */
    private function paragraph(array $block, string $children): string
    {
        $text = $this->richText($block);

        // Editors space things out with empty paragraphs; the theme's CSS does that.
        return ($text === '' ? '' : '<p>' . $text . '</p>') . $children;
    }

    /** @param array<string,mixed> $block */
    private function heading(int $level, array $block, string $children): string
    {
        $text = $this->richText($block, inHeading: true);
        if ($text === '') {
            return $children;
        }

        $tag = 'h' . min(6, $level + $this->headingOffset);

        return "<{$tag}>{$text}</{$tag}>" . $children;
    }

    /** @param array<string,mixed> $block */
    private function image(array $block): string
    {
        $image = is_array($block['image'] ?? null) ? $block['image'] : [];
        // Notion-hosted files are signed URLs valid for about an hour; the
        // content cache (ActivityContent::CACHE_TTL) must stay well below that.
        $src = $this->safeUrl($image[$image['type'] ?? '']['url'] ?? null, fileHost: true);
        if ($src === null) {
            return '';
        }

        $caption = $this->formatRichText(is_array($image['caption'] ?? null) ? $image['caption'] : []);
        $alt     = $this->escape(PageNormalizer::plainText(is_array($image['caption'] ?? null) ? $image['caption'] : []));

        return '<figure><img src="' . $this->escape($src) . '" alt="' . $alt . '" loading="lazy">'
            . ($caption === '' ? '' : '<figcaption>' . $caption . '</figcaption>')
            . '</figure>';
    }

    private function link(mixed $url): string
    {
        $href = $this->safeUrl($url);

        return $href === null
            ? ''
            : '<p><a href="' . $this->escape($href) . '">' . $this->escape($href) . '</a></p>';
    }

    /** @param array<string,mixed> $block */
    private function richText(array $block, bool $inHeading = false): string
    {
        $payload = $block[(string) ($block['type'] ?? '')] ?? [];

        return $this->formatRichText(
            is_array($payload['rich_text'] ?? null) ? $payload['rich_text'] : [],
            $inHeading
        );
    }

    /**
     * @param list<array<string,mixed>> $chunks
     * @param bool $inHeading Headings are already bold; editors bold them anyway.
     */
    private function formatRichText(array $chunks, bool $inHeading = false): string
    {
        $html = '';
        foreach ($chunks as $chunk) {
            if (! is_array($chunk)) {
                continue;
            }

            $text = $this->escape((string) ($chunk['plain_text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $text = nl2br($text, false);

            $style = is_array($chunk['annotations'] ?? null) ? $chunk['annotations'] : [];
            if (($style['code'] ?? false) === true) {
                $text = '<code>' . $text . '</code>';
            }
            if (($style['italic'] ?? false) === true) {
                $text = '<em>' . $text . '</em>';
            }
            if (($style['bold'] ?? false) === true && ! $inHeading) {
                $text = '<strong>' . $text . '</strong>';
            }
            if (($style['strikethrough'] ?? false) === true) {
                $text = '<s>' . $text . '</s>';
            }

            $href = $this->safeUrl($chunk['href'] ?? null);
            if ($href !== null) {
                $text = '<a href="' . $this->escape($href) . '">' . $text . '</a>';
            }

            $html .= $text;
        }

        return trim($html);
    }

    /**
     * Only public, absolute links. Links to other Notion pages are internal
     * (the visitor cannot open them) and are dropped, keeping the text.
     *
     * @param bool $fileHost Images are served from Notion's own file hosts.
     */
    private function safeUrl(mixed $url, bool $fileHost = false): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (! $fileHost && preg_match('/(^|\.)notion\.(so|site|com)$/', $host) === 1) {
            return null;
        }

        return $url;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
