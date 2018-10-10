<?php
namespace Highway;

use Closure;
use Zend\Diactoros\Response as ZResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Router
{
    private $response_callback;

    public function __construct()
    {
        $this->routes = new RouteCollection;
        $this->currentPath = "/";
    }

    public function get(string $path, Closure $callback): Route
    {
        return $this->addRoute("GET", $path, $callback);
    }

    public function post(string $path, Closure $callback): Route
    {
        return $this->addRoute("POST", $path, $callback);
    }

    public function put(string $path, Closure $callback): Route
    {
        return $this->addRoute("PUT", $path, $callback);
    }

    public function patch(string $path, Closure $callback): Route
    {
        return $this->addRoute("PATCH", $path, $callback);
    }

    public function delete(string $path, Closure $callback): Route
    {
        return $this->addRoute("DELETE", $path, $callback);
    }

    public function options(string $path, Closure $callback): Route
    {
        return $this->addRoute("OPTIONS", $path, $callback);
    }

    public function map(array $methods, Closure $callback): array
    {
        $routes = [];

        foreach ($methods as $method) {
            $routes = $this->addRoute($method, $path, $callback);
        }

        return $routes;
    }

    public function match(Request $request): Response
    {
        try {
            $route = $this->routes->find($request);
        } catch (\Exception $e) {
            $response = new ZResponse;
            
            // The former $response object is immutable but
            // returns a clone with the new status code
            $response = $response->withStatus(404);
            
            return $response;
        }

        return $route->dispatch($request);
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    protected function addRoute(string $method, string $path, Closure $callback): Route
    {
        $path = $this->addLeadingAndRemoveTrailingSlashes($path);
        $path = $this->currentPath . $path;
        $route = new Route($method, $path, $callback);
        $this->routes->addRoute($route);

        return $route;
    }

    protected function addLeadingAndRemoveTrailingSlashes(string $path)
    {
        $path = trim($path);
        $path = ltrim($path, '/');
        $path = rtrim($path, '/');

        return $path;
    }
}
