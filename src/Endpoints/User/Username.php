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
    public static ?array $uri = [ '/user/namecheck/[{username}]' ];

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
    public static array|bool $uriMapTypes = true;

    /**
     * Checks if a username is taken or not
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $username = $args["username"];

        if (!isset($username) || empty($username)) {
            $params = (array) $request->getParsedBody();
            $username = $params["username"];

            if (!isset($username) || empty($username)) {
                return Util::responseError($response, "No username was given");
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
