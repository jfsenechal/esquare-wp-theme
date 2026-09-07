<?php

declare(strict_types=1);

namespace Esquare\Theme\Block;

use Carbon\Carbon;
use Esquare\Theme\Notion\Activity;

/**
 * Turns an Activity into the strings the agenda markup needs.
 *
 * Dates are formatted through Carbon's own French translations rather than
 * wp_date(), so the labels stay correct whether or not the fr_BE locale files
 * are installed, and never shift a day through a timezone conversion.
 *
 * The Notion data is hand-typed: titles carry double spaces, stray quotes and
 * trailing separators. Everything is normalised here so the layout never has to
 * defend itself against the source.
 */
final class AgendaFormat
{
    private const LOCALE = 'fr';

    /** Collapse the double spaces and stray whitespace typed into Notion. */
    public static function title(Activity $activity): string
    {
        $title = preg_replace('/\s+/u', ' ', $activity->title) ?? $activity->title;

        return trim($title, " \t\n\r\0\x0B-–:·");
    }

    /** Short weekday, e.g. "mer.". */
    public static function weekday(Activity $activity): string
    {
        return $activity->start === null ? '' : self::carbon($activity->start)->isoFormat('ddd');
    }

    /** Day of month, always two digits so the column stays optically aligned. */
    public static function day(Activity $activity): string
    {
        return $activity->start === null ? '--' : $activity->start->format('d');
    }

    /** Short month, e.g. "sept.". */
    public static function month(Activity $activity): string
    {
        return $activity->start === null ? '' : self::carbon($activity->start)->isoFormat('MMM');
    }

    /** Full accessible date, e.g. "mercredi 9 septembre 2026". */
    public static function fullDate(Activity $activity): string
    {
        if ($activity->start === null) {
            return 'Date à préciser';
        }

        $label = self::carbon($activity->start)->isoFormat('dddd D MMMM YYYY');

        if ($activity->isMultiDay() && $activity->end !== null) {
            return 'Du ' . self::carbon($activity->start)->isoFormat('dddd D MMMM')
                . ' au ' . self::carbon($activity->end)->isoFormat('dddd D MMMM YYYY');
        }

        return $label;
    }

    public static function isToday(Activity $activity, ?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        if ($activity->start === null) {
            return false;
        }

        $today = $now->format('Y-m-d');
        $start = $activity->start->format('Y-m-d');
        $end   = ($activity->end ?? $activity->start)->format('Y-m-d');

        return $start <= $today && $today <= $end;
    }

    /**
     * The row's second line: when it happens, how long, and who runs it.
     *
     * Only ~18% of the activities carry a time and ~50% a duration, so the line
     * is assembled from whatever exists instead of printing empty fields.
     *
     * @return list<string>
     */
    public static function meta(Activity $activity, ?\DateTimeImmutable $now = null): array
    {
        $meta = [];

        if ($activity->isMultiDay() && $activity->end !== null) {
            $meta[] = 'Jusqu’au ' . self::carbon($activity->end)->isoFormat('D MMMM');
        }

        $meta[] = self::timeLabel($activity);

        if ($activity->duration !== null && ! $activity->isMultiDay()) {
            $meta[] = self::duration($activity->duration);
        }

        if ($activity->organisers !== []) {
            $meta[] = implode(', ', $activity->organisers);
        }

        return array_values(array_filter($meta, static fn (string $item): bool => $item !== ''));
    }

    /** "09:00 → 12:00", "09:00", or "Journée" when Notion holds a date without a time. */
    public static function timeLabel(Activity $activity): string
    {
        if ($activity->start === null || $activity->allDay) {
            return $activity->isMultiDay() ? '' : 'Journée';
        }

        $start = $activity->start->format('H:i');

        if ($activity->end !== null && ! $activity->isMultiDay()) {
            return $start . ' → ' . $activity->end->format('H:i');
        }

        return $start;
    }

    /** Notion mixes "2h00", "2:00", "6h" and "3*3h"; normalise the common shapes. */
    public static function duration(string $duration): string
    {
        $duration = trim($duration);
        $duration = str_replace(':', 'h', $duration);
        $duration = preg_replace('/h00$/', 'h', $duration) ?? $duration;

        return $duration;
    }

    /** The category shown as a drawer-tag label at the end of the row. */
    public static function tag(Activity $activity): string
    {
        return $activity->categories[0] ?? '';
    }

    /**
     * Group activities by calendar month, preserving order.
     *
     * @param list<Activity> $activities
     * @return list<array{key:string,label:string,activities:list<Activity>}>
     */
    public static function byMonth(array $activities): array
    {
        $months = [];

        foreach ($activities as $activity) {
            if ($activity->start === null) {
                continue;
            }

            $key = $activity->start->format('Y-m');

            if (! isset($months[$key])) {
                $months[$key] = [
                    'key'        => $key,
                    'label'      => self::ucfirst(self::carbon($activity->start)->isoFormat('MMMM YYYY')),
                    'activities' => [],
                ];
            }

            $months[$key]['activities'][] = $activity;
        }

        return array_values($months);
    }

    /**
     * "de septembre 2026 à juin 2027" — the span covered by the list, for the
     * page intro. Returns '' when the list is empty or spans a single month.
     *
     * @param list<Activity> $activities
     */
    public static function span(array $activities): string
    {
        $months = self::byMonth($activities);
        if (count($months) < 2) {
            return '';
        }

        $first = mb_strtolower($months[0]['label']);
        $last  = mb_strtolower($months[count($months) - 1]['label']);

        return 'de ' . $first . ' à ' . $last;
    }

    private static function carbon(\DateTimeImmutable $date): Carbon
    {
        return Carbon::instance(\DateTime::createFromImmutable($date))->locale(self::LOCALE);
    }

    private static function ucfirst(string $value): string
    {
        return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }
}
