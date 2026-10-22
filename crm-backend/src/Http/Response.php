<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public function __construct(
        public int $status = 200,
        public string $body = '',
        public array $headers = [],
    ) {
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            $status,
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }

    public static function empty(int $status = 204): self
    {
        return new self($status);
    }

    public function send(array $extraHeaders = []): void
    {
        http_response_code($this->status);
        foreach ($this->headers + $extraHeaders as $name => $value) {
            header("$name: $value");
        }
        echo $this->body;
    }
}
