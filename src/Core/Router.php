<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, callable|array>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            if (is_array($handler)) {
                [$class, $action] = $handler;
                $handler = [new $class(), $action];
            }

            $handler(...array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            return;
        }

        abort(404);
    }

    /**
     * Supports placeholders like {slug} and constrained ones like {id:\d+}.
     */
    private function add(string $method, string $path, callable|array $handler): void
    {
        $regex = preg_replace_callback(
            '#\{(\w+)(?::([^}]+))?\}#',
            fn (array $m) => sprintf('(?P<%s>%s)', $m[1], $m[2] ?? '[^/]+'),
            $path
        );

        $this->routes[$method]['#^' . $regex . '$#u'] = $handler;
    }
}
