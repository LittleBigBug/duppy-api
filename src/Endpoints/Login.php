<?php
namespace Duppy\Endpoints;

use Duppy\Abstracts\AbstractEndpoint;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Login extends AbstractEndpoint {

    /**
     * Set the URI to /login or /login/steam /login/google etc
     *
     * @var array
     */
    public static ?array $uri = [ '/login[/{provider}]' ];

    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $provider = $args["provider"];
        return $response;
    }

}
