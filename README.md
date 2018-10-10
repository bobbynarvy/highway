# Highway

Simple routing for PHP.

Largely inspired by [Ring](https://github.com/ring-clojure) and [Compojure](https://github.com/weavejester/compojure) and built with PSR-7 interfaces, an application built with the **Highway** router makes use of the following:

* Request - an object implementing `Psr\Http\Message\ServerRequestInterface`
* Response - an object implementing `Psr\Http\Message\ResponseInterface`
* Handler - a function that takes an an object implementing `Psr\Http\Message\ServerRequestInterface` and returns an object implementing `Psr\Http\Message\ResponseInterface`
* Middleware - higher-order functions that add additional functionality to handlers *(coming soon...)*

## Usage

``` php
// on index.php

use Highway\{Route, Router};
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\{ServerRequestFactory, Response};
use Psr\Http\Message\ServerRequestInterface as Request;

$request = ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$router = new Router;

$router->get("/", function (Request $request) {
    return new HtmlResponse("<h1>It works!</h1>");
});

$router->get("/users/{id}/messages/{num}", function (Request $request) {
    $id = $request->getAttribute("id");
    $num = $request->getAttribute("num");

    return new HtmlResponse("<h1>ID: $id</h1> <h1>NUM: $num</h1>");
});

$response = $router->match($request);

echo $response->getBody();
```

## Testing

``` bash
$ composer test {file or path to test}
```

## Credits

- [Robert Narvaez](https://github.com/bobbynarvy)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.