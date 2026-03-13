<?php

declare(strict_types=1);

final class Router
{
    /** @var array<int, array{method: string, path: string, handler: callable}> */
    private array $exactRoutes = [];

    /** @var array<int, array{method: string, pattern: string, handler: callable}> */
    private array $regexRoutes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->exactRoutes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function addRegex(string $method, string $pattern, callable $handler): void
    {
        $this->regexRoutes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): bool
    {
        $method = strtoupper($method);

        foreach ($this->exactRoutes as $route) {
            if ($route['method'] !== $method || $route['path'] !== $path) {
                continue;
            }

            ($route['handler'])();
            return true;
        }

        foreach ($this->regexRoutes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }

            ($route['handler'])($matches);
            return true;
        }

        return false;
    }
}
