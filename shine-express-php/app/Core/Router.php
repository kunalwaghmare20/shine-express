<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{methods: string[], pattern: string, action: callable|array{0: class-string, 1: string}, middleware: array<int, mixed>}> */
    private array $routes = [];

    /**
     * @param callable|array{0: class-string, 1: string} $action
     * @param array<int, mixed> $middleware
     */
    public function get(string $pattern, callable|array $action, array $middleware = []): void
    {
        $this->add(['GET'], $pattern, $action, $middleware);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     * @param array<int, mixed> $middleware
     */
    public function post(string $pattern, callable|array $action, array $middleware = []): void
    {
        $this->add(['POST'], $pattern, $action, $middleware);
    }

    /**
     * @param string[] $methods
     * @param callable|array{0: class-string, 1: string} $action
     * @param array<int, mixed> $middleware
     */
    public function add(array $methods, string $pattern, callable|array $action, array $middleware = []): void
    {
        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'pattern' => $pattern,
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $middleware) {
                $this->runMiddleware($middleware);
            }

            $action = $route['action'];
            if (is_array($action)) {
                [$class, $methodName] = $action;
                $controller = new $class();
                call_user_func_array([$controller, $methodName], $params);
                return;
            }

            call_user_func_array($action, $params);
            return;
        }

        Response::abort(404, 'Page not found');
    }

    private function runMiddleware(mixed $middleware): void
    {
        if (is_callable($middleware) && !is_string($middleware)) {
            $middleware();
            return;
        }

        if (is_string($middleware) && str_starts_with($middleware, 'role:')) {
            $roles = explode(',', substr($middleware, 5));
            (new \App\Middleware\RoleMiddleware($roles))->handle();
            return;
        }

        if (is_string($middleware) && str_starts_with($middleware, 'apiRole:')) {
            $roles = explode(',', substr($middleware, 8));
            (new \App\Middleware\ApiRoleMiddleware($roles))->handle();
            return;
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                $instance->handle();
            }
        }
    }

    /** @return list<string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        array_shift($matches);
        return array_values($matches);
    }
}
