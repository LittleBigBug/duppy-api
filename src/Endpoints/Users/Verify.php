<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints\Users;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\ORMException;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyException;
use Duppy\DuppyServices\UserService;
use Duppy\Entities\WebUserVerification;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Verify extends AbstractEndpoint {

    /**
     * Set the URI to /users/verify
     *
     * @var ?array
     */
    public static ?array $uri = [ '/verify' ];

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
     * Set the parent group classname to 'GroupUser'
     *
     * @var ?string
     */
    public static ?string $parentGroup = "Duppy\Endpoints\Users\GroupUsers";

    /**
     * Checks if a username is taken or not
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     * @throws ORMException
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $postArgs = $request->getParsedBody();
        $code = Util::indArrayNull($postArgs, "code");

        if (empty($code)) {
            return Util::responseError($response, "No code was given");
        }

        $dbo = Bootstrapper::getDatabase();
        $userVerify = $dbo->getRepository(WebUserVerification::class)->findOneBy([ "code" => $code ]);

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