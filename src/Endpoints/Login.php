<?php
namespace Duppy\Endpoints;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\Common\Collections\Criteria;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\Settings;
use Duppy\Bootstrapper\TokenManager;
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
     * Allow post
     *
     * @var string[]
     */
    public static array $types = [ 'post' ];

    /**
     * Handles logins with passwords or third-party (HybridAuth)
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

        $loggedIn = function ($userObj) use ($response, $respondError) {
            if ($userObj == null) {
                return $respondError("No matching user");
            }

            $userId = $userObj->getId();
            $username = $userObj->getUsername();
            $avatar = $userObj->getAvatarUrl();

            $data = [
                "user" => $userId,
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
            $postArgs = $request->getParsedBody();

            if ($postArgs == null || empty($postArgs)) {
                return $respondError("No POST arguments using pwd auth");
            }

            $user = $postArgs["user"];
            $pass = $postArgs["pass"];

            if (empty($user)) {
                return $respondError("User is empty");
            }

            if (empty($pass)) {
                return $respondError("Pass is empty");
            }

            $hash = password_hash($pass, PASSWORD_DEFAULT);

            $dbo = Bootstrapper::getContainer()->get('database');
            $expr = Criteria::expr();

            $cr = new Criteria();
            $cr->where($expr->eq("password", $hash));
            $cr->andWhere($expr->orX(
                $expr->eq("email", $user),
                $expr->eq("username", $user),
            ));

            $userObj = $dbo->getRepository("Duppy\Entities\WebUser")->matching($cr)->first();
            return $loggedIn($userObj);
        }

        $authHandler = Bootstrapper::getContainer()->get('authHandler');
        $authHandler->authenticate($provider);

        $connected = $authHandler->isConnected();

        if (!$connected) {
            return $respondError("Provider auth error");
        }

        $providerId = $authHandler->getUserProfile()->identifier;

        $dbo = Bootstrapper::getContainer()->get('database');
        $expr = Criteria::expr();

        $cr = new Criteria();
        $cr->where($expr->eq("providername", $provider));
        $cr->andWhere($expr->eq("providerid", $providerId));

        $userObj = $dbo->getRepository("Duppy\Entities\WebUserProviderAuth")->matching($cr)->first();
        return $loggedIn($userObj);
    }

}
