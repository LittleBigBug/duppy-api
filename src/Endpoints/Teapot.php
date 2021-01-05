<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints;

use Duppy\Abstracts\AbstractEndpoint;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Teapot extends AbstractEndpoint {

    public static ?array $uri = null;

    public function __invoke(Request $request, Response $response, array $args = []): Response {
        return $response->withStatus(418);
    }

}