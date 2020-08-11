<?php
namespace Highway;

use Closure;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Route
{
    /**
     * The handler to dispatch when the route matches a request
     *
     * The handler is passed an object implementing the
     * ServerRequestInterface interface and must return an
     * object implementing the ResponseInterface interface
     *
     * @var \Closure|\Psr\Http\Server\RequestHandlerInterface
     */
    protected $handler;

    /**
     * The URI path to match
     *
     * @var string
     */
    protected $path;

    /**
     * A string where {param} becoms the regex pattern ([\.a-zA-Z0-9_-]+)
     *
     * @var string
     */
    protected $pattern;

    /**
     * The keys of the parameters
     *
     * @var string[]
     */
    protected $param_keys;

    /**
     * An associative array matching param keys to their values
     *
     * @var array
     */
    protected $matches;

    /**
     * Creates a new Route instance
     *
     * @param string $method
     * @param string $path
     * @param \Closure|\Psr\Http\Server\RequestHandlerInterface $handler
     * @return void
     */
    public function __construct(string $method, string $path, $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    /**
     * Gets the path property
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Determines whether a given HTTP request matches this route
     *
     * Compares whether the request's path matches this instance's
     * pattern and whether the request method is the same as that of
     * this instance
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return boolean
     */
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

    /**
     * Gets the pattern property
     *
     * @return string
     */
    public function getPattern(): string
    {
        if (!isset($this->pattern)) {
            $this->pattern = preg_replace('~{([A-Za-z0-9]*?)}~', '([\.a-zA-Z0-9_-]+)', $this->path);
        }

        return $this->pattern;
    }


    /**
     * Gets the parameter keys
     *
     * @return array
     * @throws \Exception if parameter keys are not unique
     */
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

    /**
     * Dispatches the handler
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return Response
     */
    public function dispatch(Request $request): Response
    {
        foreach ($this->matches as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        if ($this->handler instanceof RequestHandlerInterface) {
            return $this->handler->handle($request);
        }

        return call_user_func($this->handler, $request);
    }

    /**
     * Associates a parameter key to its matching value
     *
     * @param array $matches
     * @return array
     */
    protected function pairKeysWithValues(array $matches)
    {
        $pairs = [];

        $keys = $this->getParamKeys();

        for ($i = 0; $i < count($keys); $i++) {
            $pairs[$keys[$i]] = $matches[$i + 1][0];
        }

        return $pairs;
    }
}
