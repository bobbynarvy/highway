<?php
namespace Highway;

use Closure;
use Zend\Diactoros\Response as ZResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class to match URIs to their handlers
 * 
 * An instantiated Router class is used by first setting paths to match. 
 * Each path is set using the method equivalent to the HTTP method it 
 * is intended to be handled with. The router is then passed an object
 * that instantiates the PSR-7 ServerRequestInterface interface to match 
 * and returns an object that instatiates the PSR-7 ServerRequestInterface
 * interface as a response.
 * 
 * @author Robert Narvaez <narvaez.rm@gmail.com>
 */
class Router
{
    /**
     * The current path of the router
     *
     * @var string
     */
    private $currentPath;

    /**
     * Creates a new Router instance
     * @return void
     */
    public function __construct()
    {
        $this->routes = new RouteCollection;
        $this->currentPath = "/";
    }

    /**
     * Adds a response handler to a GET request matching the supplied path
     *
     * @param string $path
     * @param \Closure $callback
     * @return \Highway\Route
     */
    public function get(string $path, Closure $callback): Route
    {
        return $this->addRoute("GET", $path, $callback);
    }

    /**
     * Adds a response handler to a POST request matching the path
     *
     * @param string $path
     * @param \Closure $callback
     * @return \Highway\Route
     */
    public function post(string $path, Closure $callback): Route
    {
        return $this->addRoute("POST", $path, $callback);
    }

    /**
     * Adds a response handler to a PUT request matching the path
     *
     * @param string $path
     * @param \Closure $callback
     * @return \Highway\Route
     */
    public function put(string $path, Closure $callback): Route
    {
        return $this->addRoute("PUT", $path, $callback);
    }

    /**
     * Adds a response handler to a PATCH request matching the path
     *
     * @param string $path
     * @param \Closure $callback
     * @return \Highway\Route
     */
    public function patch(string $path, Closure $callback): Route
    {
        return $this->addRoute("PATCH", $path, $callback);
    }

    /**
     * Adds a response handler to a DELETE request matching the path
     *
     * @param string $path
     * @param \Closure $callback
     * @return \Highway\Route
     */
    public function delete(string $path, Closure $callback): Route
    {
        return $this->addRoute("DELETE", $path, $callback);
    }

    /**
     * Adds a response handler to an OPTIONS request matching the path
     *
     * @param string $path
     * @param \Closure $callback
     * @return \Highway\Route
     */
    public function options(string $path, Closure $callback): Route
    {
        return $this->addRoute("OPTIONS", $path, $callback);
    }

    /**
     * Adds a response handler to a request matching the path and any of the supplied HTTP methods
     *
     * @param array $methods
     * @param \Closure $callback
     * @return \Highway\Route[]
     */
    public function map(array $methods, Closure $callback): array
    {
        $routes = [];

        foreach ($methods as $method) {
            $routes = $this->addRoute($method, $path, $callback);
        }

        return $routes;
    }

    /**
     * Matches an object instantiating ServerRequestInterface
     * 
     * If a match is found, the associated handler will be dispatched, 
     * returning an object instantiating ResponseInterface
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
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

    /**
     * Groups related routes that have the same path prefix
     *
     * @param string $prefix
     * @param \Closure $callback
     * @return void
     */
    public function with(string $prefix, Closure $callback)
    {
        $prefix = $this->addLeadingAndRemoveTrailingSlashes($prefix);

        $this->currentPath = $this->mergePaths($this->currentPath, $prefix);

        call_user_func($callback, $this);

        // reset the current path by removing the prefix from the end of the path
        $this->currentPath = $this->addLeadingAndRemoveTrailingSlashes(
            preg_replace('~' . $prefix . '$~', '',  $this->currentPath)
        );
    }

    /**
     * Gets the associated RouteCollection instance
     *
     * @return \Highway\RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Adds a route to the routes collection
     *
     * @param string $method
     * @param string $path
     * @param \Closure $callback
     * @return \Highway\Route
     */
    protected function addRoute(string $method, string $path, Closure $callback): Route
    {
        $path = $this->addLeadingAndRemoveTrailingSlashes($path);
        $path = $this->mergePaths($this->currentPath, $path);
        $route = new Route($method, $path, $callback);
        $this->routes->addRoute($route);

        return $route;
    }

    /**
     * Adds a leading slash and removes any trailing slashes in a string
     *
     * @param string $path
     * @return string
     */
    protected function addLeadingAndRemoveTrailingSlashes(string $path): string
    {
        $path = trim($path);
        $path = (strlen($path) == 0 || $path[0] !== "/") ? "/" . $path : $path;
        $path = rtrim($path, '/');

        return $path;
    }

    /**
     * Merges two paths together into a new path
     * 
     * @param string $path1
     * @param string $path2
     * @return string
     */
    protected function mergePaths(string $path1, string $path2): string
    {
        // Even though most paths have been trimmed of their trailing slash,
        // sometimes, just merging the two paths still returns an invalid path.
        // This is the case when the currentPath is simply '/' because 
        // the slash is both the leading and trailing slash.
        if (substr($path1, -1) == "/" && substr($path2, 0, 1) == "/") {
            return rtrim($path1, "/") . $path2;
        }

        return $path1 . $path2;
    }
}
