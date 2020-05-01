<?php
namespace Duppy\Abstracts;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

abstract class AbstractRouteMiddleware
{
    public function __construct()
    {
        echo 'Lol';
    }

    final public function __invoke(Request $request, Response $response, callable $next): Response {
        return $response;
    }
}