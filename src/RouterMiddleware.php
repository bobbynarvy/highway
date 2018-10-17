<?php
namespace Highway;

use Closure;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouterMiddleware implements MiddlewareInterface
{
    /**
     * Create a new instance of RouterMiddleware
     *
     * @param \Closure $routerHandler to pass all the router logic to
     */
    public function __construct(Closure $routerHandler)
    {
        $this->routerHandler = $routerHandler;
    }

    /**
     * Create a new instance of RouterMiddleware
     *
     * @param \Closure $routerHandler to pass all the router logic to
     */
    public static function create(Closure $routerHandler)
    {
        $middleware = new static($routerHandler);

        return $middleware;
    }

    /**
     * Process an incoming server request
     * 
     * Processes an incoming server request by matching it to
     * the router object. Returns a success response when
     * a match is found. Delegates the request to the provided
     * handler otherwise.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $router = new Router;

        call_user_func($this->routerHandler, $router);

        $router->match($request);

        if ($router->matchFound()) {
            return $router->handle($request);
        }

        return $handler->handle($request);
    }
}
