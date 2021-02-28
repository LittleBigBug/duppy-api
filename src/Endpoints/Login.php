<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\Common\Collections\Criteria;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyException;
use Duppy\DuppyServices\Logging;
use Duppy\DuppyServices\Settings;
use Duppy\DuppyServices\UserService;
use Duppy\Entities\WebUserProviderAuth;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Login extends AbstractEndpoint {

    /**
     * Set the URI to /login or /login/steam /login/google etc
     *
     * @var ?array
     */
    public static ?array $uri = [ '/login[/{provider}]' ];

    /**
     * Allow get, post
     *
     * @var string[]
     */
    public static array $types = [ 'get', 'post' ];

    /**
     * Handles logins with passwords or third-party (HybridAuth)
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $postArgs = $request->getParsedBody();
        $provider = $args["provider"];

        if (!isset($provider) || empty($provider)) {
            $provider = "password";
        }

        $providerEnabled = (new Settings)->inst()->getSetting("auth.$provider.enable") == true;

        if (!$providerEnabled) {
            return Util::responseError($response, "Provider not enabled");
        }

        $userService = (new UserService)->inst();

        if ($provider == "password") {
            if ($request->getMethod() !== "POST") {
                return Util::responseError($response, "POST Required for password auth", 405);
            }

            if ($postArgs == null || empty($postArgs)) {
                return Util::responseError($response, "No POST arguments using pwd auth");
            }

            $email = $postArgs["email"];
            $pass = $postArgs["pass"];

            if (empty($email)) {
                return Util::responseError($response, "Email is empty");
            }

            if (empty($pass)) {
                return Util::responseError($response, "Pass is empty");
            }

            $userObj = (new UserService)->inst()->getUserByEmail($email);

            if (!password_verify($pass, $userObj->get("password"))) {
                return Util::responseError($response, "Email and password do not match");
            }

            return $userService->loginUser($response, $userObj);
        }

        $profile = $userService->authenticateHybridAuth($provider, $postArgs);

        // Error
        if (is_string($profile)) {
            return Util::responseError($response, $profile);
        }

        if ($profile::class == "HybridAuth\User\Profile") {
            return Util::responseError($response, "HybridAuth authentication error");
        }

        $providerId = $profile->identifier;

        $dbo = Bootstrapper::getContainer()->get('database');
        $expr = Criteria::expr();

        $cr = new Criteria();
        $cr->where($expr->eq("providername", $provider));
        $cr->andWhere($expr->eq("providerid", $providerId));

        $userAuth = $dbo->getRepository(WebUserProviderAuth::class)->matching($cr)->first();

        // No user matching the third party authentication, so "redirect" to register instead
        if ($userAuth === false) {
            $rg = new Register;
            return $rg($request, $response, $args);
        }

        $userObj = $userAuth->get("user");

        (new Logging)->inst()->UserAction($userObj, "Authentication Provider Login with : $provider");

        if ($userObj == null) {
            return Util::responseError($response, "Cant find user associated to provider auth");
        }

        return (new UserService)->inst()->loginUser($response, $userObj, true);
    }

}
