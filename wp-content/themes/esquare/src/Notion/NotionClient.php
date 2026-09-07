<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Low-level transport for the Notion REST API.
 *
 * Deliberately WordPress-agnostic (Symfony HttpClient, no wp_remote_*) so the
 * same class can be exercised from a plain CLI script — see bin/notion-check.php —
 * as well as from inside a request. Higher-level, Notion-shaped helpers live in
 * DatabaseRepository / ActivityRepository.
 *
 * The integration token comes from NOTION_API_KEY in `.env` (loaded by
 * wp-config.php through Symfony Dotenv) and must never reach the browser.
 */
final class NotionClient
{
    public const BASE_URL = 'https://api.notion.com/v1';

    /**
     * Pinned API version. 2022-06-28 still exposes databases directly; from
     * 2025-09-03 on, a database is a container of "data sources" and queries
     * move to /v1/data_sources/{id}/query. Override via NOTION_VERSION only
     * together with the matching endpoints.
     *
     * @see https://developers.notion.com/reference/versioning
     */
    public const DEFAULT_VERSION = '2022-06-28';

    /** Notion caps page_size at 100. */
    public const MAX_PAGE_SIZE = 100;

    /** Safety cap so a pagination bug can never loop forever against the API. */
    private const MAX_PAGES = 50;

    /** Attempts for a single call when Notion answers 429 / 5xx. */
    private const MAX_ATTEMPTS = 3;

    private readonly HttpClientInterface $http;

    public function __construct(
        private readonly string $token,
        private readonly string $version = self::DEFAULT_VERSION,
        ?HttpClientInterface $http = null,
        private readonly int $timeout = 15,
    ) {
        if (trim($this->token) === '') {
            throw new NotionException('Notion token is empty (set NOTION_API_KEY in .env).', 401, 'unauthorized');
        }

        $this->http = $http ?? HttpClient::create();
    }

    /**
     * Build a client from the environment.
     *
     * @throws NotionException when NOTION_API_KEY is missing.
     */
    public static function fromEnv(?HttpClientInterface $http = null): self
    {
        $token = NotionConfig::apiKey();
        if ($token === '') {
            throw new NotionException(
                'NOTION_API_KEY is not set — add it to .env (integration token, "ntn_…" or "secret_…").',
                401,
                'unauthorized'
            );
        }

        return new self($token, NotionConfig::version(), $http);
    }

    /** The authenticated bot user — the cheapest way to prove the token works. */
    public function me(): array
    {
        return $this->get('/users/me');
    }

    /**
     * @param array<string,scalar> $query
     * @return array<string,mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query === [] ? [] : ['query' => $query]);
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    public function post(string $path, array $body = []): array
    {
        return $this->request('POST', $path, ['json' => $body === [] ? new \stdClass() : $body]);
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    public function patch(string $path, array $body): array
    {
        return $this->request('PATCH', $path, ['json' => $body]);
    }

    /**
     * Walk every page of a paginated endpoint, yielding the individual results.
     *
     * Notion paginates with `start_cursor` / `next_cursor`; GET endpoints take
     * them as query parameters, POST endpoints inside the JSON body.
     *
     * @param array<string,mixed> $payload Query params (GET) or JSON body (POST).
     * @return \Generator<int,array<string,mixed>>
     */
    public function paginate(string $method, string $path, array $payload = [], ?int $limit = null): \Generator
    {
        $cursor = null;
        $seen   = 0;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $pageSize = self::MAX_PAGE_SIZE;
            if ($limit !== null) {
                $pageSize = min($pageSize, max(1, $limit - $seen));
            }

            $params = $payload + ['page_size' => $pageSize];
            if ($cursor !== null) {
                $params['start_cursor'] = $cursor;
            }

            $body = strtoupper($method) === 'GET'
                ? $this->get($path, $params)
                : $this->post($path, $params);

            foreach (($body['results'] ?? []) as $result) {
                if (! is_array($result)) {
                    continue;
                }

                yield $result;

                if ($limit !== null && ++$seen >= $limit) {
                    return;
                }
            }

            $cursor = is_string($body['next_cursor'] ?? null) ? $body['next_cursor'] : null;
            if (($body['has_more'] ?? false) !== true || $cursor === null) {
                return;
            }
        }
    }

    /**
     * @param array<string,mixed> $options Symfony HttpClient options (query / json).
     * @return array<string,mixed>
     * @throws NotionException
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $url     = self::BASE_URL . '/' . ltrim($path, '/');
        $options += [
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization'  => 'Bearer ' . $this->token,
                'Notion-Version' => $this->version,
                'Accept'         => 'application/json',
            ],
        ];

        $lastError = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = $this->http->request($method, $url, $options);
                $status   = $response->getStatusCode();
                $raw      = $response->getContent(false);
            } catch (HttpExceptionInterface $e) {
                // Transport-level failure (DNS, TLS, timeout): worth one retry.
                $lastError = new NotionException(
                    'Notion API transport error on ' . $path . ': ' . $e->getMessage(),
                    0,
                    'transport_error',
                    $e
                );

                if ($attempt < self::MAX_ATTEMPTS) {
                    $this->backOff($attempt);
                    continue;
                }

                throw $lastError;
            }

            $decoded = json_decode($raw, true);
            $body    = is_array($decoded) ? $decoded : [];

            if ($status >= 200 && $status < 300) {
                if ($body === [] && trim($raw) !== '') {
                    throw new NotionException('Notion API returned a non-JSON body for ' . $path, $status);
                }

                return $body;
            }

            // 429 (rate limited) and 5xx are transient; everything else is ours to fix.
            if ($status === 429 || $status >= 500) {
                $lastError = NotionException::fromResponse($status, $body, $path);

                if ($attempt < self::MAX_ATTEMPTS) {
                    $retryAfter = (float) ($response->getHeaders(false)['retry-after'][0] ?? 0);
                    $this->backOff($attempt, $retryAfter);
                    continue;
                }
            }

            throw NotionException::fromResponse($status, $body, $path);
        }

        throw $lastError ?? new NotionException('Notion API request failed for ' . $path);
    }

    private function backOff(int $attempt, float $retryAfter = 0.0): void
    {
        $seconds = $retryAfter > 0 ? min($retryAfter, 10.0) : 2 ** ($attempt - 1);
        usleep((int) ($seconds * 1_000_000));
    }
}
