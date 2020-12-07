<?php
namespace Duppy\Endpoints;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\Common\Collections\Criteria;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\Settings;
use Duppy\Bootstrapper\TokenManager;
use Duppy\Bootstrapper\UserService;
use Duppy\Util;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedValueException;
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
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $postArgs = $request->getParsedBody();
        $provider = $args["provider"];

        if (!isset($provider) || empty($provider)) {
            $provider = "password";
        }

        $providerEnabled = Settings::getSetting("auth.$provider.enable") == true;

        if (!$providerEnabled) {
            return Util::responseError($response, "Provider not enabled");
        }

        $loggedIn = function ($userObj) use ($response) {
            if ($userObj == null) {
                return Util::responseError($response, "No matching user");
            }

            $userId = $userObj->get("id");
            $username = $userObj->get("username");
            $avatar = $userObj->get("avatarUrl");

            $data = [
                "id" => $userId,
                "username" => $username,
                "avatarUrl" => $avatar,
            ];

            $token = TokenManager::createTokenFill($data);

            return Util::responseJSON($response, [
                "success" => true,
                "data" => array_merge($data, [
                    "token" => $token,
                ]),
            ]);
        };

        if ($provider == "password") {
            if ($request->getMethod() !== "POST") {
                return Util::responseError($response, "POST Required for password auth", 405);
            }

            if ($postArgs == null || empty($postArgs)) {
                return Util::responseError($response, "No POST arguments using pwd auth");
            }

            $user = $postArgs["user"];
            $pass = $postArgs["pass"];

            if (empty($user)) {
                return Util::responseError($response, "User is empty");
            }

            if (empty($pass)) {
                return Util::responseError($response, "Pass is empty");
            }

            $hash = password_hash($pass, PASSWORD_DEFAULT);

            $dbo = Bootstrapper::getContainer()->get('database');
            $expr = Criteria::expr();

            $cr = new Criteria();
            $cr->where($expr->eq("password", $hash));
            $cr->andWhere($expr->orX(
                $expr->eq("email", $user),
            ));

            $userObj = $dbo->getRepository("Duppy\Entities\WebUser")->matching($cr)->first();
            return $loggedIn($userObj);
        }

        $profile = UserService::authenticateHybridAuth($provider, $postArgs);

        // Error
        if (is_string($profile)) {
            return Util::responseError($response, $profile);
        }

        if (!is_subclass_of($profile, "HybridAuth\User\Profile")) {
            return Util::responseError($response, "HybridAuth authentication error");
        }

        $providerId = $profile->identifier;

        $dbo = Bootstrapper::getContainer()->get('database');
        $expr = Criteria::expr();

        $cr = new Criteria();
        $cr->where($expr->eq("providername", $provider));
        $cr->andWhere($expr->eq("providerid", $providerId));

        $userAuth = $dbo->getRepository("Duppy\Entities\WebUserProviderAuth")->matching($cr)->first();
        $userObj = $userAuth->get("user");

        if ($userObj == null || !is_subclass_of($userObj, "Duppy\Entities\WebUser")) {
            return Util::responseError($response, "Cant find user associated to provider auth");
        }

        return $loggedIn($userObj);
    }

}
