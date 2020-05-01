<?php
namespace Duppy\Abstracts;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

abstract class AbstractRouteMiddleware
{
    final public function __invoke(Request $request, object $next): Response {

    }
}