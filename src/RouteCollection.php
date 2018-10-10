<?php
namespace Highway;

use Closure;
use Psr\Http\Message\ServerRequestInterface as Request;

class RouteCollection
{
    private $routes;

    public function __construct()
    {
        $this->routes = [];
    }

    public function addRoute(Route $route)
    {
        $this->routes []= $route;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function find(Request $request)
    {
        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                return $route;
            }
        }

        throw new \Exception("Did not find a matching route.", 1);
    }
}
