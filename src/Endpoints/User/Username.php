<?php
namespace Duppy\Endpoints\User;

use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Username extends AbstractEndpoint {

    /**
     * Set the URI to /user/namecheck/{username to check availability for}
     *
     * @var array
     */
    public static ?array $uri = [ '/user/namecheck[/{username}]' ];

    /**
     * Allow get and post
     *
     * @var string[]
     */
    public static array $types = [ 'GET', 'POST' ];

    /**
     * Map all to 1 function
     *
     * @var array|boolean
     */
    public static $uriMapTypes = true;

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

        if (!isset($username) || empty($username)) {
            $username = $request->getParsedBody()['username'];

            if (!isset($username) || empty($username)) {
                return Util::responseJSON($response, [
                    'success' => false,
                    'error' => "No username was given",
                ]);
            }
        }

        $dbo = Bootstrapper::getManager();
        $userRes = $dbo->getRepository("Duppy\Entities\WebUser")->count([ 'username' => $username, ]);

        return Util::responseJSON($response, [
            'success' => true,
            'username' => $username,
            'available' => $userRes < 1,
        ]);
    }

}
