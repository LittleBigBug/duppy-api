<?php
namespace Duppy\Endpoints;

use Doctrine\Common\Collections\Criteria;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\Settings;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Login extends AbstractEndpoint {

    /**
     * Set the URI to /login or /login/steam /login/google etc
     *
     * @var array
     */
    public static ?array $uri = [ '/login[/{provider}]' ];

    /**
     * Handles logins with passwords or third-party (HybridAuth)
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $provider = $args["provider"];

        if (!isset($provider) || empty($provider)) {
            $provider = "pwd";
        }

        $providerEnabled = Settings::getSetting("auth.$provider.enabled") == true;

        $respondError = function ($err) use ($response) {
            return $response->withJson(['success' => false, 'error' => $err]);
        };

        if (!$providerEnabled) {
            return $respondError("Provider not enabled");
        }

        if ($provider == "pwd") {
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

            $dbo = Bootstrapper::getManager();
            $expr = Criteria::expr();

            $cr = new Criteria();
            $cr->where($expr->eq("password", $hash));
            $cr->andWhere($expr->orX(
                $expr->eq("email", $user),
                $expr->eq("username", $user),
            ));

            $userObj = $dbo->getRepository("webuser")->matching($cr)->first();

            if ($userObj == null) {
                return $respondError("No matching user");
            }

            $userId = $userObj->getId();
            $username = $userObj->getUsername();
            $avatar = $userObj->getAvatarUrl();

            return $response->withJson([
                'success' => true,
                'data' => [
                    'id' => $userId,
                    'username' => $username,
                    'avatarUrl' => $avatar,
                ],
            ]);
        }

        $authHandler = $request->getAttribute("authHandler");
        $authHandler->authenticate($provider);

        $connected = $authHandler->isConnected();

        if (!$connected) {
            return $respondError("Provider auth error");
        }

        $providerId = $authHandler->getUserProfile()->identifier;

        $dbo = Bootstrapper::getManager();
        $expr = Criteria::expr();

        $cr = new Criteria();
        $cr->where($expr->eq("providername", $provider));
        $cr->andWhere($expr->eq("providerid", $providerId));

        $userObj = $dbo->getRepository("webuserproviderauth")->matching($cr)->first();

        if ($userObj == null) {
            return $respondError("No matching user");
        }

        $userId = $userObj->getId();
        $username = $userObj->getUsername();
        $avatar = $userObj->getAvatarUrl();

        return $response->withJson([
            'success' => true,
            'data' => [
                'id' => $userId,
                'username' => $username,
                'avatarUrl' => $avatar,
            ],
        ]);
    }

}
