<?php
namespace Duppy\Endpoints\User;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\UserService;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class EmailCheck extends AbstractEndpoint {

    /**
     * Set the URI to /user/email-check/{username} to check availability for}
     *
     * @var ?array
     */
    public static ?array $uri = [ '/user/email-check[/{email}]' ];

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
    public static array|bool $uriMapTypes = false;

    /**
     * Checks if a username is taken or not
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $email = $args["email"];

        if (!isset($email) || empty($email)) {
            $params = (array) $request->getParsedBody();
            $email = $params["email"];

            if (!isset($email) || empty($email)) {
                return Util::responseError($response, "No email was given");
            }
        }

        $taken = UserService::emailTaken($email);
        $needsVerification = $taken ? UserService::emailNeedsVerification($email) : false;

        return Util::responseJSON($response, [
            'success' => true,
            'data' => [
                'email' => $email,
                'available' => !$taken,
                'needsVerify' => $needsVerification,
            ],
        ]);
    }

}
