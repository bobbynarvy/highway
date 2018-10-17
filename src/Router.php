<?php
namespace Highway;

use Closure;
use Zend\Diactoros\Response as ZResponse;
use Psr\Http\Server\RequestHandlerInterface;
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
     * To keep track of whether a match is found
     *
     * @var bool
     */
    private $matchFound;

    /**
     * The Route object that has been matched
     *
     * @var Route
     */
    private $match;

    /**
     * Creates a new Router instance
     * @return void
     */
    public function __construct()
    {
        $this->routes = new RouteCollection;
        $this->currentPath = "/";
        $this->matchFound = false;
    }

    /**
     * Adds a response handler to a GET request matching the supplied path
     *
     * @param string $path
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Highway\Route
     */
    public function get(string $path, $handler): Route
    {
        return $this->addRoute("GET", $path, $handler);
    }

    /**
     * Adds a response handler to a POST request matching the path
     *
     * @param string $path
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Highway\Route
     */
    public function post(string $path, $handler): Route
    {
        return $this->addRoute("POST", $path, $handler);
    }

    /**
     * Adds a response handler to a PUT request matching the path
     *
     * @param string $path
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Highway\Route
     */
    public function put(string $path, $handler): Route
    {
        return $this->addRoute("PUT", $path, $handler);
    }

    /**
     * Adds a response handler to a PATCH request matching the path
     *
     * @param string $path
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Highway\Route
     */
    public function patch(string $path, $handler): Route
    {
        return $this->addRoute("PATCH", $path, $handler);
    }

    /**
     * Adds a response handler to a DELETE request matching the path
     *
     * @param string $path
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Highway\Route
     */
    public function delete(string $path, $handler): Route
    {
        return $this->addRoute("DELETE", $path, $handler);
    }

    /**
     * Adds a response handler to an OPTIONS request matching the path
     *
     * @param string $path
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Highway\Route
     */
    public function options(string $path, $handler): Route
    {
        return $this->addRoute("OPTIONS", $path, $handler);
    }

    /**
     * Adds a response handler to a request matching the path and any of the supplied HTTP methods
     *
     * @param array $methods
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Highway\Route[]
     */
    public function map(array $methods, $handler): array
    {
        $routes = [];

        foreach ($methods as $method) {
            $routes = $this->addRoute($method, $path, $handler);
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
    public function match(Request $request): Router
    {
        try {
            $this->match = $this->routes->find($request);
            $this->matchFound = true;
        } catch (\Exception $e) {
            $this->matchFound = false;
        }

        return $this;
    }

    /**
     * Checks if a match has been found
     *
     * @return boolean
     */
    public function matchFound(): bool
    {
        return $this->matchFound;
    }

    public function handle(Request $request): Response
    {
        if (!$this->matchFound) {
            $response = new ZResponse;
            
            // The former $response object is immutable but
            // returns a clone with the new status code
            $response = $response->withStatus(404);
            
            return $response;
        }

        return $this->match->dispatch($request);
    }

    /**
     * Groups related routes that have the same path prefix
     *
     * @param string $prefix
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return void
     */
    public function with(string $prefix, $handler)
    {
        $prefix = $this->addLeadingAndRemoveTrailingSlashes($prefix);

        // set the current path by merging the current path with the prefix.
        // This way all router calls from within the handler will be prefixed.
        $this->currentPath = $this->mergePaths($this->currentPath, $prefix);

        call_user_func($handler, $this);

        // reset the current path by removing the prefix from the end of the path.
        // This is necessary to return to an earlier prefix especially after a 
        // with() has been called from within another with() call
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
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Highway\Route
     */
    protected function addRoute(string $method, string $path, $handler): Route
    {
        $this->checkHandlerValidity($handler);

        $path = $this->addLeadingAndRemoveTrailingSlashes($path);
        $path = $this->mergePaths($this->currentPath, $path);
        $route = new Route($method, $path, $handler);
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

    /**
     * Checks whether a handler is valid
     *
     * @param mixed $handler
     * @return void
     * @throws \Exception when the handler is not valid
     */
    protected function checkHandlerValidity($handler)
    {
        $is_a_proper_closure = $this->checkClosureHandlerValidity($handler);

        // check if the handler implements RequestHandlerInterface
        $is_a_req_interface = $handler instanceof RequestHandlerInterface;

        if (!$is_a_proper_closure && !$is_a_req_interface) {
            throw new \Exception(
                "Handler must be an implementation of RequestHandlerInterface or 
                be a Closure that takes an implementation of ServerRequestInterface
                and returns an implementation of ResponseInterface"
            );
        }        
    }

    /**
     * Checks if the handler is a closure and it has the right param type
     *
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return bool
     */
    private function checkClosureHandlerValidity($handler): bool
    {
        if (get_class($handler) === \Closure::class) {
            $reflection = new \ReflectionFunction($handler);
            $args = $reflection->getParameters();

            if (count($args) > 0) {
                $param_class_name = $args[0]->getClass()->name;
                return $param_class_name === Request::class;
            }
        }

        return false;
    }
}
