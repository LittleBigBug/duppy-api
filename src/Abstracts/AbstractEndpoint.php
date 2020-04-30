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
    public string $type;

    /**
     * Handles the response
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    abstract public function respond(Request $request, Response $response, array $args = []): Response;

    /**
     * Returns type of request
     *
     * @return string
     */
    final public function getType(): string
    {
        return $this->type;
    }
}