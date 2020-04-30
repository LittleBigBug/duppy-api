<?php
namespace Duppy\Abstracts;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

abstract class AbstractEndpoint
{
    /**
     * Type of request
     *
     * @var string
     */
    public static string $type;

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
}