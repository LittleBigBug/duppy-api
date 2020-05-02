<?php
namespace Duppy\Middleware;

use Duppy\Abstracts\AbstractRouteMiddleware;
use Psr\Http\Message\ResponseInterface as Response;

class TestMiddleware extends AbstractRouteMiddleware
{
    final public function handle(): Response
    {
        $response = self::getResponse();
        $response->getBody()->write('Middleware!');

        return $response;
    }
}