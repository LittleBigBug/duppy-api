<?php

namespace Duppy\Endpoints;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\MailService;
use Duppy\Bootstrapper\Settings;
use Duppy\Bootstrapper\TokenManager;
use Duppy\Bootstrapper\UserService;
use Duppy\Entities\WebUser;
use Duppy\Entities\WebUserProviderAuth;
use Duppy\Entities\WebUserVerification;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Register extends AbstractEndpoint {

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
        $provider = Util::indArrayNull($args, "provider");

        if (empty($provider)) {
            $provider = "password";
        }

        $providerEnabled = UserService::enabledProvider($provider);
        $postArgs = $request->getParsedBody();

        if (!$providerEnabled) {
            return Util::responseError($response, "Provider not enabled");
        }

        if ($provider == "password") {
            if ($postArgs == null || empty($postArgs)) {
                return Util::responseError($response, "No POST arguments using pwd auth");
            }

            $email = Util::indArrayNull($postArgs, "email");
            $pass = Util::indArrayNull($postArgs, "pass");

            $err = "";

            if (empty($email)) {
                $err = "Email is empty";
            } elseif (UserService::emailTaken($email)) {
                $err = "Email is taken";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err = "Invalid email";
            }

            if (empty($pass)) {
                $err = "Pass is empty";
            }

            if (!empty($err)) {
                return Util::responseError($response, $err);
            }

            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $dbo = Bootstrapper::getContainer()->get('database');

            $uVerify = new WebUserVerification;

            $uVerify->setEmail($email);
            $uVerify->setPassword($hash);

            $code = $uVerify->genCode();

            if (!$code) {
                return Util::responseError($response, "There was an error generating a verification code");
            }

            $url = getenv("CLIENT_URL") . "#/login/verification";

            $title = Settings::getSetting("title");
            $subject = "Verify Your New $title Account";

            MailService::sendMailTemplate($email, $subject, "verifyAccount", [
                "url" => $url,
                "code" => $code,
                "title" => $subject,
            ],
            // todo - replace with localization system
                "Your account is almost ready! Verify it by following this link $url/$code or by inputting this code: $code");

            $dbo->persist($uVerify);
            $dbo->flush();

            return Util::responseJSON($response, [
                "success" => true,
                "message" => "User needs to be verified",
            ], 201);
        }

        $profile = UserService::authenticateHybridAuth($provider, $postArgs);

        // Error
        if (is_string($profile)) {
            return Util::responseError($response, $profile);
        }

        if ($profile::class == "HybridAuth\User\Profile") {
            return Util::responseError($response, "HybridAuth authentication error");
        }

        $providerId = $profile->identifier;
        $username = $profile->displayName;
        $email = $profile->emailVerified ?? ""; // This email may not be provided but we can ask the user later
        $avatar = $profile->photoURL ?? "";

        $dbo = Bootstrapper::getContainer()->get("database");

        // Create new account from provider info
        $userObj = new WebUser;

        $userObj->setEmail($email);
        $userObj->setUsername($username);
        $userObj->setAvatarUrl($avatar);

        $registerAuth = new WebUserProviderAuth;

        $registerAuth->setProviderName($provider);
        $registerAuth->setProviderId($providerId);
        $registerAuth->setUser($userObj);

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

        $redirect = getenv("CLIENT_URL") . "#/login/success/" . $token  . "/" . $data["id"];
        return $response->withHeader("Location", $redirect)->withStatus(302);
    }

}