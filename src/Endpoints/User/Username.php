<?php
namespace Duppy\Endpoints\User;

use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Username extends AbstractEndpoint {

    /**
     * Set the URI to /user/namecheck/{username to check availability for}
     *
     * @var array
     */
    public static ?array $uri = [ '/user/namecheck/{username}' ];

    /**
     * Handles logins with passwords or third-party (HybridAuth)
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $username = $args["username"];

        $dbo = Bootstrapper::getManager();
        $userRes = $dbo->getRepository("webuser")->count([ 'username' => $username, ]);

        return $response->withJson([
            'username' => $username,
            'available' => $userRes < 1,
        ]);
    }

}
