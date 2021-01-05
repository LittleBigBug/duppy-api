<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints\User;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyServices\UserService;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Verify extends AbstractEndpoint {

    /**
     * Set the URI to /user/email-check/{username} to check availability for}
     *
     * @var ?array
     */
    public static ?array $uri = [ '/user/verify' ];

    /**
     * Allow post
     *
     * @var string[]
     */
    public static array $types = [ 'POST' ];

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
        $postArgs = $request->getParsedBody();
        $code = Util::indArrayNull($postArgs, "code");

        if (empty($code)) {
            return Util::responseError($response, "No code was given");
        }

        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        $userVerify = $dbo->getRepository("Duppy\Entities\WebUserVerification")->findBy([ "code" => $code ])->first();

        if ($userVerify == null) {
            return Util::responseError($response, "That code has either expired or is invalid");
        }

        // Already hashed here
        $email = $userVerify->get("email");
        $pass = $userVerify->get("password");

        $userService = (new UserService)->inst();
        $user = $userService->createUser($email, $pass);
        return $userService->loginUser($response, $user);
    }

}