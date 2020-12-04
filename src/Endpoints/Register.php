<?php

namespace Duppy\Endpoints;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\TokenManager;
use Duppy\Bootstrapper\UserService;
use Duppy\Entities\WebUser;
use Duppy\Entities\WebUserProviderAuth;
use Duppy\Entities\WebUserVerification;
use Duppy\Util;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedValueException;
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
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $provider = $args["provider"];
        $providerEnabled = UserService::enabledProvider($provider);
        $postArgs = $request->getParsedBody();

        if (!$providerEnabled) {
            return Util::responseError($response, "Provider not enabled");
        }

        if ($provider == "password") {
            if ($postArgs == null || empty($postArgs)) {
                return Util::responseError($response, "No POST arguments using pwd auth");
            }

            $username = $postArgs["username"];
            $email = $postArgs["email"];
            $pass = $postArgs["pass"];
            $passConf = $postArgs["passConf"];

            $err = "";

            if (empty($username)) {
                $err = "Username is empty";
            }

            if (UserService::getUserByName($username) !== null) {
                $err = "Username is taken";
            }

            if (empty($email)) {
                $err = "Email is empty";
            }

            if (UserService::getUserByEmail($email) !== null) {
                $err = "Email is taken";
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err = "Invalid email";
            }

            if (empty($pass)) {
                $err = "Pass is empty";
            }

            if (empty($passConf)) {
                $err = "Pass Conf is empty";
            }

            if ($passConf !== $pass) {
                $err = "Pass Conf does not match pass";
            }

            if (!empty($err)) {
                return Util::responseError($response, $err);
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

        $profile = UserService::authenticateHybridAuth($provider, $postArgs);

        if (is_subclass_of($profile, "Slim\Psr7\Response")) {
            return $response;
        }

        if (!is_subclass_of($profile, "HybridAuth\User\Profile")) {
            return Util::responseError($response, "HybridAuth authentication error");
        }

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