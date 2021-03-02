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
use Duppy\DuppyServices\UserService;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ForgotPassword extends AbstractEndpoint {

    /**
     * Catch /forgot-pass and /forgot-pass/redeem
     *
     * GET /user/0/forgot-pass
     * GET /user/0/forgot-pass/verify
     *
     * @var string[]
     */
    public static ?array $uri = [ '/forgot-pass', '/forgot-pass/verify/{code}', ];

    /**
     * Allow get and post
     *
     * @var string[]
     */
    public static array $types = [ 'GET', ];

    /**
     * Map all to 1 function
     *
     * @var array|boolean
     */
    public static array|bool $uriMapTypes = false;

    /**
     * Map /verify the use the verify function in this class
     *
     * @var string[]
     */
    public static ?array $uriFuncNames = [ 1 => 'verify' ];

    /**
     * Set the parent group classname to 'GroupUser'
     *
     * @var ?string
     */
    public static ?string $parentGroup = "Duppy\Endpoints\User\GroupUser";

    /**
     * Forgot password requires captcha verification
     *
     * @var array
     */
    public static array $middleware = [ "Duppy\Middleware\CaptchaMiddleware" ];

    /**
     * Default invoke method
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $userId = $args["id"];
        $user = (new UserService)->inst()->getUser($userId);

        if ($user == null) {
            return Util::responseError($response, "That user doesn't exist");
        }



        return $response;
    }

    /**
     * This function will be called when /forgot-pass/verify/{code} is the uri
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function verify(Request $request, Response $response, array $args = []): Response {

    }

}