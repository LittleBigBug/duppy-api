<?php
namespace Duppy\Abstracts;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Duppy\Bootstrapper\Bootstrapper;
use DI\Container;

abstract class AbstractEndpoint
{
    /**
     * Type(s) of requests accepted
     *
     * @var array
     */
    public static array $types = [ 'get' ];

    /**
     * Endpoint URI (defaults to path)
     *
     * @var string|null
     */
    public static string $uri;

    /**
     * Route middleware storage
     *
     * @var array
     */
    public static array $middleware = [];

    /**
     * Handles the response
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    abstract public function __invoke(Request $request, Response $response, array $args = []): Response;

    /**
     * Returns type(s) of requests accepted
     *
     * @return array
     */
    final public static function getTypes(): array
    {
        return static::$types;
    }

    /**
     * Returns uri
     *
     * @return string|null
     */
    final public static function getUri(): string
    {
        return static::$uri ?? '';
    }

    /**
     * Returns the middleware for the endpoint
     *
     * @return array
     */
    final public static function getMiddleware(): array
    {
        return static::$middleware;
    }

    /**
     * Returns dependency container instance
     *
     * @return Container
     */
    final public static function getContainer(): Container
    {
        return Bootstrapper::getContainer();
    }
}