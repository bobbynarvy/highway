# Highway

[![Build Status](https://travis-ci.org/bobbynarvy/highway.svg?branch=master)](https://travis-ci.org/bobbynarvy/highway)

Simple routing for PHP. PSR-7 and PSR-15 compatible.

**Highway** is a component for routing HTTP requests to their handlers. 

<!-- As a component, its intended use case is kept to a minimum. All other features normally associated with routers (e.g. assigning and dispatching middleware, dependency injection on handlers) are not included on purpose and should be dealt with by other components.  -->

## Install

Via Composer

``` bash
$ composer require bobbynarvy/highway
```

## Usage

**Highway** can be used as a stand-alone router that can be integrated in any PHP script as well as a PSR-15 middleware.

### Basic usage: As a stand-alone router

A basic scenario where the router is used would look like:

``` php
// on index.php...

use Highway\{Route, Router};

// PSR-7 and PSR-15 interfaces
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

// Implementations of PSR-7 and PSR-15
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\{ServerRequestFactory, Response};
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

// Create an instance of PSR-7 ServerRequestInterface object
// using Zend\Diactoros
$request = ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

// Create a new instance of Highway\Router
$router = new Router;

// Set the handler for HTTP requests going to the root path
$router->get("/", function (ServerRequestInterface $request) {
    // Return an instance of HtmlResponse, an implementation of 
    // Psr\Http\Message\ResponseInterface;
    return new HtmlResponse("<h1>It works!</h1>");
});

// Set the handler for a route with parameters
$router->get("/users/{id}/messages/{num}", function (ServerRequestInterface $request) {
    // Assign the attributes to their respective variables using the 
    // getAttribute() method of Psr\Http\Message\ServerRequestInterface
    $id = $request->getAttribute("id");
    $num = $request->getAttribute("num");
    
    return new HtmlResponse("<h1>ID: $id</h1> <h1>NUM: $num</h1>");
});

// Get the response to the request that matches a defined route
$response = $router->match($request)->handle($request);

// Emit the response to the HTTP client
$emitter = new SapiEmitter;
$emitter->emit($response);
```

*Note: The example above assumes that URL rewrites are enabled to remove 'index.php' from the URI*

### Methods

To register routes, the router can be used with the fellowing methods that correspond to their HTTP methods

``` php
$router->get($path, $handler);
$router->post($path, $handler);
$router->put($path, $handler);
$router->patch($path, $handler);
$router->delete($path, $handler);
$router->options($path, $handler);
``` 

Multiple HTTP methods can also be mapped to respond the same way. For example:

``` php
$router->map(["GET", "POST"], $handler);
```

### Route prefixing

The router can be used to prefix routes with a given path:

``` php
$router->with("/users/{id}", function(Router $router) {
    // will respond to /users/{id}/likes routes
    $router->get("/likes", $likeHandler); 

    // will respond to /users/{id}/friends/{fid} routes
    $router->get("/friends/{fid}", $friendHandler); 
});
```
### Handlers

Handlers are passed to the router along with a route. They define actions that are dispatched when the router finds a route that matches the request. 

#### Closures

A handler can be defined through a closure that takes an an object implementing [PSR-7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) and returns an object implementing [PSR-7 ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)

``` php
use Zend\Diactoros\Response\HtmlResponse;

$handler = function (ServerRequestInterface $request) {
    // return an instance of HtmlResponse, an implementation of 
    // Psr\Http\Message\ResponseInterface;
    return new HtmlResponse("<h1>It works!</h1>");
}
```

#### PSR-15 RequestHandlerInterface

A handler can be defined using an object that implements the [PSR-15 RequestHandlerInterface](https://www.php-fig.org/psr/psr-15/#21-psrhttpserverrequesthandlerinterface)

``` php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

$handler = new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse("<h1>It works!</h1>");
    }
};
```

#### Request parameters

Request parameters can be accessed using the `getAttribute` method of a *PSR-7 ServerRequestInterface* object:

``` php
$router->get("/users/{id}/messages/{num}", function (ServerRequestInterface $request) {
    $id = $request->getAttribute("id");
    /* ... */
});
```

### As a PSR-15 Middleware

**Highway** can be used to create a PSR-15 compliant middleware. Unlike other router implementations, which themselves assign and dispatch middlewares, **Highway** itself *is* a middleware!

``` php
/*...*/
use Highway\{Router, RouterMiddleware};
use Zend\Diactoros\Response\HtmlResponse;
/*...*/

$routerMiddleware = RouterMiddleware::create(function (Router $router) {
    $router->get("/", function (ServerRequestInterface $request) {
        return new HtmlResponse("<h1>It works!</h1>");
    });
    /* ... */
});
```

**Highway** can then be used alongside other reusable PSR-15 middlewares. Using the [`middlewares/utils`](https://github.com/middlewares/utils#dispatcher) Dispatcher, for example:

``` php
/*...*/
use Middlewares\Utils\Dispatcher;
/*...*/

$routerMiddleware =  RouterMiddleware::create(function (Router $router) {
    // define routes...
});

$response = Dispatcher::run([
    $someAuthMiddleware,
    $someLoggingMiddleware,
    $routerMiddleware
]);
```

## Testing

``` bash
$ composer test {file or path to test}
```

## Credits

- [Robert Narvaez](https://github.com/bobbynarvy)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.