<?php

declare(strict_types=1);

namespace EduQR;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $callback): void
    {
        $this->addRoute('GET', $path, $callback);
    }

    public function post(string $path, callable $callback): void
    {
        $this->addRoute('POST', $path, $callback);
    }

    public function patch(string $path, callable $callback): void
    {
        $this->addRoute('PATCH', $path, $callback);
    }

    public function delete(string $path, callable $callback): void
    {
        $this->addRoute('DELETE', $path, $callback);
    }

    private function addRoute(string $method, string $path, callable $callback): void
    {
        // Rota parametrelerini regex'e dönüştür (örn: {id} -> (?P<id>[^/]+))
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[$method][] = [
            'pattern'  => $pattern,
            'callback' => $callback,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Query string ayıklama (örn: /path?query=1 -> /path)
        $uri = explode('?', $uri)[0];

        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Sadece isimlendirilmiş regex parametrelerini filtrele
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func($route['callback'], $params);
                return;
            }
        }

        // Bulunamadıysa 404
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>The page you requested could not be found.</p>";
        exit;
    }
}
