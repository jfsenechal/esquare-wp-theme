<?php

declare(strict_types=1);

namespace Esquare\Theme\Notion;

/**
 * Any failure while talking to the Notion REST API: transport error, non-2xx
 * response, or an unparsable body.
 *
 * `$code` carries Notion's own error code ("unauthorized", "object_not_found",
 * "validation_error", ...) when the API returned one — it is far more useful
 * than the HTTP status alone for deciding whether a retry can help.
 *
 * @see https://developers.notion.com/reference/status-codes
 */
final class NotionException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly string $notionCode = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    /** @param array<string,mixed> $body Decoded Notion error payload, if any. */
    public static function fromResponse(int $status, array $body, string $path): self
    {
        $code    = is_string($body['code'] ?? null) ? $body['code'] : '';
        $message = is_string($body['message'] ?? null) ? $body['message'] : 'Unknown Notion API error';

        return new self(
            sprintf('Notion API %s %s: %s', $status, $path, $message),
            $status,
            $code
        );
    }

    /** True when the failure is worth surfacing as "not configured" rather than "broken". */
    public function isAuthFailure(): bool
    {
        return $this->status === 401 || $this->notionCode === 'unauthorized';
    }

    public function isNotFound(): bool
    {
        return $this->status === 404 || $this->notionCode === 'object_not_found';
    }
}
