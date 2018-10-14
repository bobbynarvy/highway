<?php
namespace Highway;

use Closure;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Holds instances of the Route class
 */
class RouteCollection
{
    /**
     * Array which contains instances of the Route class
     *
     * @var \Highway\Route[]
     */
    private $routes;

    /**
     * Creates a new instance of the RouteCollection class
     * 
     * @return void
     */
    public function __construct()
    {
        $this->routes = [];
    }

    /**
     * Adds an instance of the Route class to the routes property
     *
     * @param \Highway\Route $route
     * @return void
     */
    public function addRoute(Route $route)
    {
        $this->routes []= $route;
    }

    /** Gets the routes property
     * 
     * @return \Highway\Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Finds an instance of the Route class which matches the given request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Highway\Route
     * @throws \Exception if no matching Route instance is found
     */
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
