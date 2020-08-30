<?php

use Highway\Route;
use PHPUnit\Framework\TestCase;
use Laminas\Diactoros\{Response, ServerRequestFactory};
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\ServerRequestInterface as Psr7Request;

class RouteTest extends TestCase
{
    /**
     * @dataProvider routeParamsProvider
     */
    public function testConvertsStringParamsToRegexString($a, $b, $c)
    {
        $route = new Route($a, $b, function() {});

        $pattern = $route->getPattern();

        $this->assertSame($c, $pattern);
    }

    public function routeParamsProvider()
    {
        return [
            ["GET", "/users/{user_id}", "/users/([\.a-zA-Z0-9_-]+)"],
            ["GET", "/users/{id}/messages/{id}", "/users/([\.a-zA-Z0-9_-]+)/messages/([\.a-zA-Z0-9_-]+)"],
            ["GET", "/some-route/{param-1}", "/some-route/([\.a-zA-Z0-9_-]+)"]
        ];
    }

    /**
     * @expectedException Exception
     */
    public function testThrowsExceptionWhenKeysAreNotUnique()
    {
        $route = new Route("GET", "/users/{id}/messages/{id}", function() {});

        $route->getParamKeys();
    }

    public function testStoresParameterKeys()
    {
        $route = new Route("GET", "/users/{id}/orders/{num}", function() {});

        $param_keys = $route->getParamKeys();

        $this->assertSame(["id", "num"], $param_keys);
    }

    public function testFindsAMatchWithAClosure()
    {
        $route = new Route(
            "GET", "/users/{id}/orders/{num}",
            function(Psr7Request $request) {
                $this->assertSame("1-1", $request->getAttribute("id"));

                $this->assertSame("2-2", $request->getAttribute("num"));

                return new Response;
            }
        );

        $requestFactory = new ServerRequestFactory;

        $request = $requestFactory->createServerRequest("GET", "/users/1-1/orders/2-2");

        $route->matches($request);

        $route->dispatch($request);
    }

    public function testFindsAMatchWithARequestHandler()
    {
        $handler = new class($this) implements RequestHandlerInterface {
            public function __construct($test)
            {
                $this->test = $test;
            }

            public function handle(Psr7Request $request): Psr7Response
            {
                $this->test->assertSame("1", $request->getAttribute("id"));

                $this->test->assertSame("2", $request->getAttribute("num"));

                return new Response;
            }
        };

        $route = new Route("GET", "/users/{id}/orders/{num}", $handler);

        $requestFactory = new ServerRequestFactory;

        $request = $requestFactory->createServerRequest("GET", "/users/1/orders/2");

        $route->matches($request);

        $route->dispatch($request);
    }

    public function testFindsNoMatchIfRequestMethodIsDifferentFromRouteMethod()
    {
        $route = new Route("POST", "/users", function() {});

        $requestFactory = new ServerRequestFactory;

        $request = $requestFactory->createServerRequest("GET", "/users");

        $match = $route->matches($request);

        $this->assertFalse($match);
    }
}