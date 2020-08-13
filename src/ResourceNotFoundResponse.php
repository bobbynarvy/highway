<?php

namespace Highway;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResourceNotFoundResponse implements ResponseInterface
{
    public function getStatusCode()
    {
        return 404;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        return $this;
    }

    public function getReasonPhrase()
    {
        return 'Not Found';
    }

    public function getProtocolVersion()
    {
        return '1.1';
    }

    public function withProtocolVersion($version)
    {
        return $this;
    }

    public function getHeaders()
    {
        return [];
    }

    public function hasHeader($name)
    {
        return false;
    }

    public function getHeader($name)
    {
        return [];
    }

    public function getHeaderLine($name)
    {
        return '';
    }

    public function withHeader($name, $value)
    {
        return $this;
    }

    public function withAddedHeader($name, $value)
    {
        return $this;
    }

    public function withoutHeader($name)
    {
        return $this;
    }

    public function getBody()
    {
        return '';
    }

    public function withBody(StreamInterface $body)
    {
        return $this;
    }
}