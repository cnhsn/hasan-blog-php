<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private string $method;
    private string $path;
    /** @var array<string,mixed> */
    private array $query;
    /** @var array<string,mixed> */
    private array $post;
    /** @var array<string,mixed> */
    private array $files;
    /** @var array<string,string> */
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->path = rtrim((string) $uriPath, '/');
        if ($this->path === '') {
            $this->path = '/';
        }
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->headers = self::extractHeaders();
    }

    /** @return array<string,string> */
    private static function extractHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            }
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /** @return array<string,mixed> */
    public function allPost(): array
    {
        return $this->post;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtoupper($name)] ?? $this->headers[$name] ?? null;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function origin(): ?string
    {
        return $_SERVER['HTTP_ORIGIN'] ?? null;
    }
}
