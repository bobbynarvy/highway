<?php

use PHPUnit\Framework\TestCase;
use Highway\{Route, RouteCollection};
use Laminas\Diactoros\ServerRequestFactory;

class RouteCollectionTest extends TestCase
{
    public function testAddsRoute()
    {
        $coll = new RouteCollection;

        $route = new Route("GET", "/users", function() {});

        $coll->addRoute($route);

        $this->assertNotEmpty($coll->getRoutes());
    }

    /**
     * @dataProvider routesProvider
     */
    public function testFindsAMatchingRoute($a, $b)
    {
        $coll = new RouteCollection;

        $route = new Route("GET", $a, function() {});

        $coll->addRoute($route);

        $requestFactory = new ServerRequestFactory;

        $request = $requestFactory->createServerRequest('GET', $b);

        $match = $coll->find($request);

        $this->assertSame($route, $match);
    }

    public function routesProvider()
    {
        return [
            ["/", "/"],
            ["/users", "/users"],
            ["/users/{id}/messages/{id2}", "/users/1/messages/1"]
        ];
    }

    /**
     * @expectedException     Exception
     * @expectedExceptionCode 1
     */
    public function testThrowsAnExceptionIfNoMatchFound()
    {
        $coll = new RouteCollection;

        $requestFactory = new ServerRequestFactory;

        $request = $requestFactory->createServerRequest('GET', '/route-that-does-not-exist');

        $match = $coll->find($request);
    }
}
