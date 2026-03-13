<?php

declare(strict_types=1);

final class Request
{
    /** @param array<string, mixed> $query
     *  @param array<string, mixed> $post
     */
    public function __construct(
        private string $method,
        private string $path,
        private array $query,
        private array $post
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

        /** @var array<string, mixed> $query */
        $query = is_array($_GET ?? null) ? $_GET : [];
        /** @var array<string, mixed> $post */
        $post = is_array($_POST ?? null) ? $_POST : [];

        return new self($method, $path, $query, $post);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /** @return array<string, mixed> */
    public function queryAll(): array
    {
        return $this->query;
    }

    /** @return array<string, mixed> */
    public function postAll(): array
    {
        return $this->post;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }
}
