<?php
namespace Duppy\Endpoints;

use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Settings;
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
        $providerEnabled = Settings::getSetting("auth.$provider.enabled") == true;

        if (!$providerEnabled && $provider !== "pwd") {
            return $response->withJson(['success' => false]);
        }

        $authHandler = $request->getAttribute("authHandler");
        $authHandler->authenticate($provider);

        return $response->withJson(['success' => $authHandler->isConnected()]);
    }

}
