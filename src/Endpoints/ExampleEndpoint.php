<?php
namespace Duppy\Endpoints;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Duppy\Abstracts\AbstractEndpoint;

class ExampleEndpoint extends AbstractEndpoint
{
    /**
     * Type of request
     *
     * @var string
     */
    public string $type = 'get';

    /**
     * Handles the response
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    final public function respond(Request $request, Response $response, array $args = []): Response
    {
        $response->getBody()->write('Test');
        return $response;
    }
}