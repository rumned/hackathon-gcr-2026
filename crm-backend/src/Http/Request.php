<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        private readonly string $body,
        private readonly string $contentType,
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // When the app lives in a subfolder (e.g. /crm/public), strip that prefix.
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if ($base !== '' && str_starts_with($path, $base . '/')) {
            $path = substr($path, strlen($base));
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            '/' . trim($path, '/'),
            $_GET,
            (string) file_get_contents('php://input'),
            (string) ($_SERVER['CONTENT_TYPE'] ?? ''),
        );
    }

    public function isJson(): bool
    {
        return str_starts_with(strtolower(trim($this->contentType)), 'application/json');
    }

    /** The JSON body as an array. An empty body gives []. */
    public function json(): array
    {
        if (trim($this->body) === '') {
            return [];
        }
        try {
            $data = json_decode($this->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new HttpError(400, 'Request body is not valid JSON');
        }
        if (!is_array($data)) {
            throw new HttpError(400, 'Request body must be a JSON object');
        }

        return $data;
    }
}
