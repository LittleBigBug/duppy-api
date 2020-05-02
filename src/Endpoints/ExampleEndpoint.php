<?php
namespace Duppy\Endpoints;

use Duppy\Middleware\TestMiddleware;
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
    public static string $type = 'get';

    /**
     * Route middleware
     *
     * @var array
     */
    public static array $middleware = [
        TestMiddleware::class,
    ];

    /**
     * Handles the response
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    final public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        $response->getBody()->write('Test');
        return $response;
    }
}