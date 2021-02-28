<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractEmailWhitelist;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyException;
use Duppy\DuppyServices\Env;
use Duppy\DuppyServices\MailService;
use Duppy\DuppyServices\Settings;
use Duppy\DuppyServices\TokenManager;
use Duppy\DuppyServices\UserService;
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
     * Any registrations require captcha (even hybridauth providers - upon pressing a provider button )
     *
     * @var array
     */
    public static array $middleware = [ "Duppy\Middleware\CaptchaMiddleware" ];

    /**
     * Handles Registers with passwords or third-party (HybridAuth)
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
        $provider = Util::indArrayNull($args, "provider");

        if (empty($provider)) {
            $provider = "password";
        }

        $userService = (new UserService)->inst();
        $settingsMngr = (new Settings)->inst();

        $providerEnabled = $userService->enabledProvider($provider);
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
            } elseif ($userService->emailTaken($email)) {
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

            $whitelist = $userService->getEmailWhitelist();

            $emailWhitelisted = false;
            $bypassVerify = false;

            if (is_subclass_of($whitelist, AbstractEmailWhitelist::class)) {
                $emailWhitelisted = $userService->emailWhitelisted($email);
                $emailWlSettings = $settingsMngr->getSettings([
                    "auth.emailWhitelist.requiredRegister",
                    "auth.emailWhitelist.bypassVerification",
                ]);

                $requiredRegister = $emailWlSettings["auth.emailWhitelist.requiredRegister"];
                $bypassVerify = $emailWlSettings["auth.emailWhitelist.bypassVerification"];

                if ($requiredRegister && !$emailWhitelisted) {
                    $desc = $whitelist::getDescription();
                    return Util::responseError($response, $desc ?? "Email is not in whitelist.");
                }
            }

            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $dbo = Bootstrapper::getContainer()->get('database');

            $canBypass = $emailWhitelisted && $bypassVerify;

            if (!$canBypass) {
                $uVerify = new WebUserVerification;

                $uVerify->setEmail($email);
                $uVerify->setPassword($hash);

                $code = $uVerify->genCode();

                if (!$code) {
                    return Util::responseError($response, "There was an error generating a verification code");
                }

                $s = $settingsMngr->getSettings(["clientUrl", "title"]);

                $clientUrl = $s["clientUrl"];
                $title = $s["title"];

                $url = "$clientUrl#/login/verification";
                $subject = "Verify Your New $title Account";

                (new MailService)->inst()->sendMailTemplate($email, $subject, "verifyAccount", [
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

            $user = $userService->createUser($email, $hash);
            return $userService->loginUser($response, $user);
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
        $username = $profile->displayName;
        $email = $profile->emailVerified ?? ""; // This email may not be provided but we can ask the user later
        $avatar = $profile->photoURL ?? "";

        if ((new UserService)->inst()->emailTaken($email)) {
            return Util::responseError($response, "That email is being used already!");
        }

        $dbo = Bootstrapper::getContainer()->get("database");

        // Create new account from provider info
        $userObj = new WebUser;

        $userObj->setEmail($email);
        $userObj->setUsername($username);
        $userObj->setAvatarUrl($avatar);
        $userObj->setCrumb("");

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
        $token = (new TokenManager)->inst()->createTokenFill($data);

        $clientUrl = $settingsMngr->getSetting("clientUrl");
        $redirect = $clientUrl . "#/login/success/" . $token  . "/" . $data["id"];
        return $response->withHeader("Location", $redirect)->withStatus(302);
    }

}