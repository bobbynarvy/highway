<?php

use Highway\{Route, Router};
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\{ServerRequestFactory, Response};
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\ServerRequestInterface as Psr7Request;


class RouterTest extends TestCase
{
    public function testAddedRoutePathHasALeadingButNoTrailingSlashes()
    {
        $router = new Router;

        $route = $router->get("/users/", function () {});

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
}
