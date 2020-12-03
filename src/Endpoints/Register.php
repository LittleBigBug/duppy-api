<?php

namespace Duppy\Endpoints;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\Common\Collections\Criteria;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\Settings;
use Duppy\Bootstrapper\TokenManager;
use Duppy\Bootstrapper\UserService;
use Duppy\Entities\WebUser;
use Duppy\Entities\WebUserProviderAuth;
use Duppy\Entities\WebUserVerification;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Register {

    /**
     * Set the URI to /register or /register/steam /register/google etc
     *
     * @var ?array
     */
    public static ?array $uri = [ '/register[/{provider}]' ];

    /**
     * Allow post
     *
     * @var string[]
     */
    public static array $types = [ 'post' ];

    /**
     * Handles Registers with passwords or third-party (HybridAuth)
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $provider = $args["provider"];

        if (!isset($provider) || empty($provider)) {
            $provider = "password";
        }

        $providerEnabled = Settings::getSetting("auth.$provider.enable") == true;

        $respondError = function ($err) use ($response) {
            return Util::responseJSON($response, ['success' => false, 'error' => $err]);
        };

        if (!$providerEnabled) {
            return $respondError("Provider not enabled");
        }

        if ($provider == "password") {
            $postArgs = $request->getParsedBody();

            if ($postArgs == null || empty($postArgs)) {
                return $respondError("No POST arguments using pwd auth");
            }

            $username = $postArgs["username"];
            $email = $postArgs["email"];
            $pass = $postArgs["pass"];
            $passConf = $postArgs["passConf"];

            if (empty($username)) {
                return $respondError("Username is empty");
            }

            if (UserService::getUserByName($username) !== null) {
                return $respondError("Username is taken");
            }

            if (empty($email)) {
                return $respondError("Email is empty");
            }

            if (UserService::getUserByEmail($email) !== null) {
                return $respondError("Email is taken");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $respondError("Invalid email");
            }

            if (empty($pass)) {
                return $respondError("Pass is empty");
            }

            if (empty($passConf)) {
                return $respondError("Pass Conf is empty");
            }

            if ($passConf !== $pass) {
                return $respondError("Pass Conf does not match pass");
            }

            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $dbo = Bootstrapper::getContainer()->get('database');

            $uVerify = new WebUserVerification([
                'email' => $email,
                'password' => $hash,
                'username' => $username,
            ]);

            $dbo->persist($uVerify);
            $dbo->flush();

            return Util::responseJSON($response, [
                "success" => true,
                "message" => "User needs to be verified",
            ], 201);
        }

        $authHandler = Bootstrapper::getContainer()->get('authHandler');
        $authHandler->authenticate($provider);

        $connected = $authHandler->isConnected();

        if (!$connected) {
            return $respondError("Provider auth error");
        }

        $profile = $authHandler->getUserProfile();

        $providerId = $profile->identifier;
        $username = $profile->displayName;
        $email = $profile->emailVerified ?? ""; // This email may not be provided but we can ask the user later
        $avatar = $profile->photoURL ?? "";

        $dbo = Bootstrapper::getContainer()->get("database");

        // Create new account from provider info
        $userObj = new WebUser([
            "email" => $email,
            "username" => $username,
            "avatarUrl" => $avatar,
        ]);

        $registerAuth = new WebUserProviderAuth([
            "providername" => $provider,
            "providerid" => $providerId,
            "user" => $userObj,
        ]);

        $userObj->addProviderAuth($registerAuth);

        $dbo->persist($userObj);
        $dbo->persist($registerAuth);

        $dbo->flush();

        $data = [
            "id" => $userObj->get("id"),
            "username" => $username,
            "avatarUrl" => $avatar,
        ];

        // Login Immediately
        $token = TokenManager::createTokenFill($data);

        return Util::responseJSON($response, [
            "success" => true,
            "data" => array_merge($data, [
                "token" => $token,
            ]),
        ]);
    }

}