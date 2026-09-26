<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{method:string,pattern:string,regex:string,handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function any(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method() === 'HEAD' ? 'GET' : $request->method();
        $path = $request->path();

        $pathMatchedOtherMethod = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $pathMatchedOtherMethod = true;
                continue;
            }

            $params = array_filter($matches, static fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);
            ($route['handler'])($request, $params);
            return;
        }

        if ($pathMatchedOtherMethod) {
            http_response_code(405);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Method Not Allowed';
            return;
        }

        Response::notFound();
    }
}
