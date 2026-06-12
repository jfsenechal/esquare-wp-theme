<?php

declare(strict_types=1);

namespace Esquare\Theme\Reservation;

/**
 * Thin client for the external "salles.marche.be" reservation API.
 *
 * Handles Sanctum token auth (login + cached bearer token) and fetching the
 * non-expired entries (bookings) for a given room. Credentials live in `.env`
 * (ESQUARE_API_BASE / ESQUARE_API_EMAIL / ESQUARE_API_PASSWORD) and never reach
 * the browser — the front end talks to our own REST proxy, see ReservationForm.
 */
final class EntriesApi
{
    private const TOKEN_TRANSIENT = 'esquare_api_token';
    private const TOKEN_TTL       = 12 * HOUR_IN_SECONDS;

    /** Safety cap so a runaway pagination loop can never hammer the API. */
    private const MAX_PAGES = 20;

    /**
     * Whether forwarding is enabled. Off until ESQUARE_API_CREATE_PATH is set,
     * so we never blind-POST to the live system before the route is confirmed.
     */
    public static function isCreateConfigured(): bool
    {
        return self::env('ESQUARE_API_CREATE_PATH') !== '';
    }

    /**
     * Create one entry (booking) on the external API.
     *
     * @param array<string,mixed> $entry
     * @return array{status:int,body:mixed} HTTP status (0 on transport error) and decoded body.
     */
    public static function createEntry(array $entry): array
    {
        if (! self::isCreateConfigured()) {
            return ['status' => 0, 'body' => null];
        }

        $token = self::token();
        if ($token === '') {
            return ['status' => 0, 'body' => null];
        }

        $result = self::post(self::createPath(), $entry, $token);
        if ($result['status'] === 401) {
            delete_transient(self::TOKEN_TRANSIENT);
            $token = self::token(true);
            if ($token === '') {
                return ['status' => 0, 'body' => null];
            }
            $result = self::post(self::createPath(), $entry, $token);
        }

        return $result;
    }

    /**
     * Busy slots for a room between today and one year out, as calendar events.
     *
     * @return list<array{date:string,start:string,end:string,title:string}>
     */
    public static function busySlots(int $roomId): array
    {
        if ($roomId <= 0) {
            return [];
        }

        $tz    = wp_timezone();
        $today = (new \DateTimeImmutable('today', $tz))->format('Y-m-d');
        $until = (new \DateTimeImmutable('+1 year', $tz))->format('Y-m-d');

        $query = [
            'room_id'    => $roomId,
            'start_date' => $today,
            'end_date'   => $until,
        ];

        $slots = [];
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $body = self::get('/entries', $query + ['page' => $page]);
            if ($body === null) {
                break;
            }

            foreach (($body['data'] ?? []) as $entry) {
                $slot = self::toSlot($entry);
                if ($slot !== null) {
                    $slots[] = $slot;
                }
            }

            $lastPage = (int) ($body['meta']['last_page'] ?? 1);
            if ($page >= $lastPage) {
                break;
            }
        }

        return $slots;
    }

    /**
     * @param array<string,mixed> $entry
     * @return array{date:string,start:string,end:string,title:string}|null
     */
    private static function toSlot(array $entry): ?array
    {
        $start = isset($entry['start_time']) ? (int) $entry['start_time'] : 0;
        $end   = isset($entry['end_time']) ? (int) $entry['end_time'] : 0;
        if ($start <= 0) {
            return null;
        }

        // Use the site timezone so the displayed day matches the venue's local day.
        return [
            'date'  => wp_date('Y-m-d', $start),
            'start' => wp_date('H:i', $start),
            'end'   => $end > 0 ? wp_date('H:i', $end) : '',
            // Public availability: never expose the bookers' names, only "busy".
            'title' => __('Occupé', 'esquare'),
        ];
    }

    /**
     * GET a JSON endpoint with a valid bearer token, retrying once after a
     * fresh login if the cached token was rejected.
     *
     * @param array<string,scalar> $query
     * @return array<string,mixed>|null
     */
    private static function get(string $path, array $query): ?array
    {
        $token = self::token();
        if ($token === '') {
            return null;
        }

        $response = self::request($path, $query, $token);
        if ($response === 401) {
            delete_transient(self::TOKEN_TRANSIENT);
            $token = self::token(true);
            if ($token === '') {
                return null;
            }
            $response = self::request($path, $query, $token);
        }

        return is_array($response) ? $response : null;
    }

    /**
     * @param array<string,scalar> $query
     * @return array<string,mixed>|int|null Decoded body, or HTTP 401 on auth failure.
     */
    private static function request(string $path, array $query, string $token)
    {
        $url = self::base() . $path . '?' . http_build_query($query);

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        if (is_wp_error($response)) {
            error_log('[esquare] entries request failed: ' . $response->get_error_message());
            return null;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code === 401) {
            return 401;
        }
        if ($code < 200 || $code >= 300) {
            error_log('[esquare] entries request HTTP ' . $code . ' for ' . $path);
            return null;
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * POST a JSON body to an endpoint.
     *
     * @param array<string,mixed> $body
     * @return array{status:int,body:mixed} HTTP status (0 on transport error) and decoded body.
     */
    private static function post(string $path, array $body, string $token): array
    {
        $response = wp_remote_post(self::base() . $path, [
            'timeout' => 15,
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body'    => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            error_log('[esquare] create entry request failed: ' . $response->get_error_message());
            return ['status' => 0, 'body' => null];
        }

        $code    = (int) wp_remote_retrieve_response_code($response);
        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);

        return ['status' => $code, 'body' => $decoded];
    }

    /** Cached bearer token, logging in when missing (or when $force is true). */
    private static function token(bool $force = false): string
    {
        if (! $force) {
            $cached = get_transient(self::TOKEN_TRANSIENT);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $token = self::login();
        if ($token !== '') {
            set_transient(self::TOKEN_TRANSIENT, $token, self::TOKEN_TTL);
        }

        return $token;
    }

    private static function login(): string
    {
        $email    = self::env('ESQUARE_API_EMAIL');
        $password = self::env('ESQUARE_API_PASSWORD');
        if ($email === '' || $password === '') {
            error_log('[esquare] API credentials not configured (.env).');
            return '';
        }

        $response = wp_remote_post(self::base() . '/login', [
            'timeout' => 15,
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'email'       => $email,
                'password'    => $password,
                'device_name' => 'esquare-wp',
            ]),
        ]);

        if (is_wp_error($response)) {
            error_log('[esquare] login failed: ' . $response->get_error_message());
            return '';
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
        $token   = is_array($decoded) ? ($decoded['token'] ?? '') : '';

        if (! is_string($token) || $token === '') {
            error_log('[esquare] login returned no token (HTTP ' . wp_remote_retrieve_response_code($response) . ').');
            return '';
        }

        return $token;
    }

    private static function base(): string
    {
        return rtrim(self::env('ESQUARE_API_BASE', 'https://salles.marche.be/api/esquare'), '/');
    }

    /** Path (relative to the base) the create-entry POST is sent to. */
    private static function createPath(): string
    {
        return '/' . ltrim(self::env('ESQUARE_API_CREATE_PATH'), '/');
    }

    private static function env(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }

        return (string) $value;
    }
}
