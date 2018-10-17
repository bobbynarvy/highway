<?php

use Highway\{Route, Router};
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\{ServerRequestFactory, Response};
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\ServerRequestInterface as Psr7Request;
use Psr\Http\Server\RequestHandlerInterface;


class RouterTest extends TestCase
{
    /**
     * @expectedException Exception 
     */
    public function testThrowsExceptionWhenClosureHandlerIsIncorrect()
    {
        $router = new Router;

        $route = $router->get("/users/", function () {});
    }

    /**
     * @expectedException Exception 
     */
    public function testThrowsExceptionWhenRequestHandlerClassIsIncorrect()
    {
        $router = new Router;

        $route = $router->get("/users/", (new class {}));
    }

    public function testAddedRoutePathHasALeadingButNoTrailingSlashes()
    {
        $router = new Router;

        $route = $router->get("/users/", function (Psr7Request $request) {});

        $route_path = $route->getPath();

        $this->assertSame("/", substr($route_path, 0, 1));

        $this->assertNotSame("", substr($route_path, -1, 1));
    }

    public function testMatchesRequestAndReturnsPsr7Response()
    {
        $path = '/hello-world';

        $requestFactory = new ServerRequestFactory;

        $request = $requestFactory->createServerRequest('GET', $path);

        $router = new Router;

        $router->get($path, function (Psr7Request $request) use ($path) {
            $this->assertSame($path, $request->getUri()->getPath());

            return new Response;
        });

        $response = $router->match($request);

        $this->assertInstanceOf(Psr7Response::class, $response);
    }

    public function testReturnsA404ResponseWhenNoMatchIsFound()
    {
        $requestFactory = new ServerRequestFactory;

        $request = $requestFactory->createServerRequest('GET', '/inexistent-path');

        $router = new Router;

        $response = $router->match($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @dataProvider prefixedRoutesProvider
     */
    public function testMatchesPrefixedRoutes($a, $b)
    {
        $requestFactory = new ServerRequestFactory;

        $request = $requestFactory->createServerRequest('GET', $b);

        $router = new Router;

        $router->with("/users/{id}", function(Router $router) use ($a, $b) {
            $router->get($a, function (Psr7Request $request) use ($b) {
                $this->assertSame($b, $request->getUri()->getPath());
    
                return new Response;
            });
        });

        $response = $router->match($request);
    }

    public function prefixedRoutesProvider()
    {
        return [
            ["/", "/users/1"],
            ["/messages", "/users/1/messages"],
            ["/messages/{id2}", "/users/1/messages/1"]
        ];
    }
}
