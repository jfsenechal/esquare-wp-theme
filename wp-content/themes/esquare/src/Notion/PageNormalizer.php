<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Flattens a Notion page object into plain PHP values.
 *
 * Notion wraps every property in its type ("Titre" => ['type' => 'title',
 * 'title' => [ …rich text… ]]), which is unusable in a Twig template. This
 * turns a page into:
 *
 *   ['id' => …, 'url' => …, 'created_time' => …, 'properties' => ['Titre' => 'Yoga', …]]
 *
 * Unknown/new property types degrade to their raw payload rather than throwing,
 * so a schema change in Notion never fatals the front end.
 */
final class PageNormalizer
{
    /**
     * @param array<string,mixed> $page Raw page object from the API.
     * @return array{id:string,url:string,icon:?string,cover:?string,created_time:string,last_edited_time:string,archived:bool,properties:array<string,mixed>}
     */
    public static function normalize(array $page): array
    {
        $properties = [];
        foreach (($page['properties'] ?? []) as $name => $property) {
            if (is_array($property)) {
                $properties[(string) $name] = self::value($property);
            }
        }

        return [
            'id'               => (string) ($page['id'] ?? ''),
            'url'              => (string) ($page['url'] ?? ''),
            'icon'             => self::fileUrl($page['icon'] ?? null),
            'cover'            => self::fileUrl($page['cover'] ?? null),
            'created_time'     => (string) ($page['created_time'] ?? ''),
            'last_edited_time' => (string) ($page['last_edited_time'] ?? ''),
            'archived'         => (bool) ($page['archived'] ?? false),
            'properties'       => $properties,
        ];
    }

    /**
     * @param list<array<string,mixed>> $pages
     * @return list<array<string,mixed>>
     */
    public static function normalizeAll(array $pages): array
    {
        return array_map(self::normalize(...), $pages);
    }

    /**
     * One property value, reduced to a scalar / list / null.
     *
     * @param array<string,mixed> $property
     */
    public static function value(array $property): mixed
    {
        $type = (string) ($property['type'] ?? '');
        $raw  = $property[$type] ?? null;

        return match ($type) {
            'title', 'rich_text' => self::plainText(is_array($raw) ? $raw : []),
            'number', 'checkbox', 'url', 'email', 'phone_number' => $raw,
            'select', 'status' => is_array($raw) ? ($raw['name'] ?? null) : null,
            'multi_select' => is_array($raw)
                ? array_values(array_map(static fn (array $o): string => (string) ($o['name'] ?? ''), $raw))
                : [],
            'date' => is_array($raw)
                ? ['start' => $raw['start'] ?? null, 'end' => $raw['end'] ?? null, 'time_zone' => $raw['time_zone'] ?? null]
                : null,
            'people' => is_array($raw)
                ? array_values(array_map(static fn (array $p): string => (string) ($p['name'] ?? $p['id'] ?? ''), $raw))
                : [],
            'files' => is_array($raw)
                ? array_values(array_filter(array_map(self::fileUrl(...), $raw)))
                : [],
            // Relations are ids only; resolve them with DatabaseRepository::retrievePage().
            'relation' => is_array($raw)
                ? array_values(array_map(static fn (array $r): string => (string) ($r['id'] ?? ''), $raw))
                : [],
            'formula' => is_array($raw) ? self::value($raw) : null,
            'rollup'  => self::rollup(is_array($raw) ? $raw : []),
            'unique_id' => is_array($raw)
                ? trim(((string) ($raw['prefix'] ?? '')) . '-' . ((string) ($raw['number'] ?? '')), '-')
                : null,
            'created_time', 'last_edited_time' => (string) $raw,
            'created_by', 'last_edited_by' => is_array($raw) ? ($raw['name'] ?? $raw['id'] ?? null) : null,
            'string', 'boolean' => $raw,
            default => $raw,
        };
    }

    /**
     * Concatenated plain text of a rich-text array — Notion splits a single
     * sentence into several chunks as soon as part of it is styled.
     *
     * @param list<array<string,mixed>> $richText
     */
    public static function plainText(array $richText): string
    {
        $parts = array_map(
            static fn (mixed $chunk): string => is_array($chunk) ? (string) ($chunk['plain_text'] ?? '') : '',
            $richText
        );

        return trim(implode('', $parts));
    }

    /** @param array<string,mixed> $rollup */
    private static function rollup(array $rollup): mixed
    {
        $type = (string) ($rollup['type'] ?? '');

        if ($type === 'array') {
            $items = is_array($rollup['array'] ?? null) ? $rollup['array'] : [];

            return array_values(array_map(
                static fn (mixed $item): mixed => is_array($item) ? self::value($item) : $item,
                $items
            ));
        }

        return $rollup[$type] ?? null;
    }

    /** URL of a file / icon object, whatever its flavour (external, uploaded, emoji). */
    private static function fileUrl(mixed $file): ?string
    {
        if (! is_array($file)) {
            return null;
        }

        return match ($file['type'] ?? '') {
            'external' => isset($file['external']['url']) ? (string) $file['external']['url'] : null,
            'file'     => isset($file['file']['url']) ? (string) $file['file']['url'] : null,
            'emoji'    => isset($file['emoji']) ? (string) $file['emoji'] : null,
            default    => null,
        };
    }
}
