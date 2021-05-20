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
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyException;
use Duppy\DuppyServices\UserService;
use Duppy\DuppyServices\MailService;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\DuppyServices\Settings;
use Duppy\Entities\PasswordResetRequest;
use Duppy\Entities\WebUser;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ForgotPassword extends AbstractEndpoint {

    /**
     * Catch /forgot-pass and /forgot-pass/redeem
     *
     * GET /users/forgot-pass/{email}
     * GET /users/forgot-pass/verify/{code}/{userId}
     *
     * @var string[]
     */
    public static ?array $uri = [ '/forgot-pass/{email}', '/forgot-pass/verify/{code}/{userId}', ];

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
    public static ?string $parentGroup = "Duppy\Endpoints\Users\GroupUsers";

    /**
     * Forgot password requires captcha verification (when requesting)
     *
     * @var array
     */
    public static array $mappedMiddleware = [
        0 => [ "Duppy\Middleware\CaptchaMiddleware" ],
    ];

    /**
     * Default invoke method
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     * @throws ORMException
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $email = Util::indArrayNull($args, "email");

        if ($email == null || empty($email)) {
            return Util::responseError($response, "Email is empty or invalid");
        }

        $userService = (new UserService)->inst();
        $user = $userService->getUserByEmail($email);
        $userId = $user->get("id");

        $defaultResponse = function() use (&$response) {
            return Util::responseJSON($response, [
                "success" => true,
                "message" => "If an account is associated with that email address a recovery email will be sent",
            ]);
        };

        if ($user == null || !($user instanceof WebUser)) {
            return $defaultResponse();
        }

        $dbo = Bootstrapper::getDatabase();
        
        $activeRequests = $userService->getActivePasswordRequests($userId);

        if ($activeRequests > 2) {
            return Util::responseError($response, "The user associated with this email already has too many forgotten password requests.");
        }

        // Generate random code
        $rnum = (mt_rand() / mt_getrandmax()) * 9999;
        $code = hash("sha256", $rnum . $user->get("id") . microtime(true)); // Code given to user (temp password)
        $hash = password_hash($code, PASSWORD_DEFAULT); // Stored hash of above code

        $code = "$userId#$code"; // Code given to the client via email

        // Store new reset request
        $resetRequest = new PasswordResetRequest;

        $resetRequest->setUser($user);
        $resetRequest->setCode($hash);

        $dbo->persist($resetRequest);
        $dbo->flush();

        // Get API URL from request
        $clientUrl = (new Settings)->inst()->getSetting("clientUrl");

        // Direct API Url will redirect to the web client
        $url = "$clientUrl#/login/forgot-password/$code";
        $subject = "Account Recovery - Password Reset";

        $result = (new MailService)->inst()->sendMailTemplate($email, $subject, "forgotPassword", [
            "url" => $url,
            "code" => $code,
            "title" => $subject,
        ],
            // todo - replace with localization system
            "A forgot password link was requested for your account. If you did not request this you can safely ignore this email. To recover your account visit: $url");

        if (!$result) {
            return Util::responseError($response, "There was an error sending an email. Please try again later");
        }

        return $defaultResponse();
    }

    /**
     * This function will be called when /forgot-pass/verify/{code} is the uri
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
    public function verify(Request $request, Response $response, array $args = []): Response {
        $codeStr = Util::indArrayNull($args, "code");

        if ($codeStr == null || empty($codeStr)) {
            return Util::responseError($response, "No code provided");
        }

        $indSep = strpos($codeStr, "#");
        $userId = substr($codeStr, 0, $indSep);
        $code = substr($codeStr, $indSep + 1);

        if (empty($code) || empty($userId)) {
            return Util::responseError($response, "Malformed code provided");
        }

        $userService = (new UserService)->inst();

        $postArgs = $request->getParsedBody();
        $newPassword = Util::indArrayNull($postArgs, "newPassword");

        if (empty($newPassword)) {
            return Util::responseError($response, "Password is empty");
        }

        $passError = "";
        $securePassword = $userService->securePassword($newPassword, $passError);

        if (!$securePassword) {
            return Util::responseError($response, $passError);
        }

        $anyValid = $userService->checkPasswordResetCode($code, $userId);

        if (!$anyValid) {
            return Util::responseError($response, "Expired or invalid code");
        }

        $user = $userService->getUser($userId);

        // this shouldn't happen lul
        if ($user != null && !($user instanceof WebUser)) {
            return Util::responseError($response, "No user found (malformed code..?)");
        }

        $dbo = Bootstrapper::getDatabase();

        $user->setPassword($newPassword);

        $dbo->persist($user);
        $dbo->flush();

        $email = $user->get("email");
        $subject = "Account Alert - Password Changed";

        (new MailService)->inst()->sendMailTemplate($email, $subject, "passwordChanged", [
            "title" => $subject,
        ],
            // todo - replace with localization system
            "A password reset was requested for your account and your password was changed. If you did not request this your account may be compromised, and you should change your passwords immediately. If you did this no further action is required");
    }

}