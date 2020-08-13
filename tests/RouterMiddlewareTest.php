<?php

use PHPUnit\Framework\TestCase;
use Highway\{Router, RouterMiddleware};
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response;

class RouterMiddlewareTest extends TestCase
{
    public function testReturnsSuccessResponseWhenMatchIsFound()
    {
        $middleware = RouterMiddleware::create(function (Router $router) {
            $router->get("/", function (ServerRequestInterface $request) {
                return new Response;
            });
        });

        $requestFactory = new ServerRequestFactory;
        $request = $requestFactory->createServerRequest('GET', "/");

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response;
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPassesRequestToHandlerWhenNoMatchIsFound()
    {
        $middleware = RouterMiddleware::create(function (Router $router) {
            // Do nothing...
        });

        $requestFactory = new ServerRequestFactory;
        $request = $requestFactory->createServerRequest('GET', "/");

        $failure_handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Response;
                return $response->withStatus(404);
            }
        };

        $response = $middleware->process($request, $failure_handler);

        $this->assertSame(404, $response->getStatusCode());
    }
}
