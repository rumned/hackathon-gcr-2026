<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;
use App\Http\Request;
use App\Http\Response;

/**
 * Matches "METHOD /path/{param}" routes in the order they were added.
 * Routes need a logged-in user unless added with $public = true.
 */
final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler, false);
    }

    public function post(string $pattern, callable $handler, bool $public = false): void
    {
        $this->add('POST', $pattern, $handler, $public);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->add('PUT', $pattern, $handler, false);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add('DELETE', $pattern, $handler, false);
    }

    private function add(string $method, string $pattern, callable $handler, bool $public): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = ['method' => $method, 'regex' => $regex, 'handler' => $handler, 'public' => $public];
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }
            if ($route['method'] !== $request->method) {
                $pathMatched = true;
                continue;
            }
            if (!$route['public']) {
                Auth::authenticate();
            }
            $params = array_map('rawurldecode', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            $result = ($route['handler'])($request, $params);

            return $result instanceof Response ? $result : Response::json($result);
        }

        throw $pathMatched ? new HttpError(405, 'Method not allowed') : new HttpError(404, 'Not found');
    }
}
