<?php
namespace Highway;

use Closure;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Route
{
    private $handler;

    private $path;

    private $pattern;

    private $param_keys;

    private $matches;

    public function __construct(string $method, string $path, Closure $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function matches(Request $request): bool
    {
        $path = $request->getUri()->getPath();

        $path_has_match = preg_match_all('~^'.$this->getPattern().'$~', $path, $matches);

        $has_same_method = $request->getMethod() == $this->method;

        if ($path_has_match && $has_same_method) {
            $this->matches = $this->pairKeysWithValues($matches);

            return true;
        }

        return false;
    }

    public function getPattern(): string
    {
        if (!isset($this->pattern)) {
            $this->pattern = preg_replace('~{([A-Za-z0-9]*?)}~', '(\w+)', $this->path);
        }

        return $this->pattern;
    }


    public function getParamKeys(): array
    {
        if (isset($this->param_keys)) {
            return $this->param_keys;
        }

        $match = preg_match_all("~\{([A-Za-z0-9]+)\}~", $this->path, $matches);

        $this->param_keys = [];

        if ($match) {
            foreach ($matches[1] as $param) {
                if (in_array($param, $this->param_keys)) {
                    throw new \Exception(
                        "Route parameters should be unique. Please modify the route " .
                        $this->path
                    );
                }

                $this->param_keys []= $param;
            }
        }

        return $this->param_keys;
    }

    public function getMatch()
    {
        return $this->match_object;
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->matches as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return call_user_func($this->handler, $request);
    }

    private function pairKeysWithValues($matches)
    {
        $pairs = [];

        $keys = $this->getParamKeys();

        for ($i = 0; $i < count($keys); $i++) {
            $pairs[$keys[$i]] = $matches[$i + 1][0];
        }

        return $pairs;
    }
}
