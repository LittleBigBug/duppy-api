<?php
namespace Duppy\Abstracts;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Duppy\Bootstrapper\Bootstrapper;
use DI\Container;

abstract class AbstractEndpoint
{
    /**
     * Type of request
     *
     * @var string
     */
    public static string $type = 'get';

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
     * Returns type of request
     *
     * @return string
     */
    final public static function getType(): string
    {
        return static::$type;
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