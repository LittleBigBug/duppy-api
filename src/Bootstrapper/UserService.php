<?php

namespace Duppy\Bootstrapper;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Entities\WebUser;
use Duppy\Util;
use Exception;
use Hybridauth\User\Profile;
use Slim\Psr7\Response;

final class UserService {

    /**
     * Convenience function to get a user by their ID
     *
     * @param $id
     * @return ?WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getUser($id = null): ?WebUser {
        if ($id == "me" || $id == null) {
            return UserService::getLoggedInUser();
        }

        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        return $dbo->find("Duppy\Entities\WebUser", $id);
    }

    /**
     * Convenience function to get a user by their Username
     *
     * @param string $username
     * @return ?WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getUserByName(string $username): ?WebUser {
        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        return $dbo->getRepository("Duppy\Entities\WebUser")->findBy([ "username" => $username ])->first();
    }

    /**
     * Convenience function to get a user by their Email
     *
     * @param string $email
     * @return ?WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getUserByEmail(string $email): ?WebUser {
        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        return Util::indArrayNull($dbo->getRepository("Duppy\Entities\WebUser")->findBy([ "email" => $email ]), 0);
    }

    /**
     * Create a new user with their email and hashed password.
     * The username will automatically be created from the email before the @
     *
     * @param string $email
     * @param string $password
     * @param bool $persist
     * @return ?WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function createUser(string $email, string $password, bool $persist = true): ?WebUser {
        $user = new WebUser;

        // Steam style
        // bob.minecraft2006
        $user->setUsername(strtok($email, "@"));

        $user->setEmail($email);
        $user->setPassword($password);
        $user->setCrumb("");

        if ($persist) {
            $container = Bootstrapper::getContainer();
            $dbo = $container->get("database");

            $dbo->persist($user);
            $dbo->flush();
        }

        return $user;
    }

    /**
     * Returns a response with the WebUser's credentials or a redirect to login
     *
     * @param Response $response
     * @param WebUser $user
     * @param bool $redirect
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function loginUser(Response $response, WebUser $user, bool $redirect = false): Response {
        if ($user == null) {
            return Util::responseError($response, "No matching user");
        }

        $userId = $user->get("id");
        $username = $user->get("username");
        $avatar = $user->get("avatarUrl");

        $data = [
            "id" => $userId,
            "username" => $username,
            "avatarUrl" => $avatar,
        ];

        $token = TokenManager::createTokenFill($data);
        $crumb = hash("sha256", $token);

        $user->setCrumb($crumb);

        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        $dbo->persist($user);
        $dbo->flush();

        if ($redirect) {
            $redirect = getenv("CLIENT_URL") . "#/login/success/" . $token . "/" . $crumb . "/" . $data["id"];
            return $response->withHeader("Location", $redirect)->withStatus(302);
        } else {
            return Util::responseJSON($response, [
                "success" => true,
                "data" => [
                    "token" => $token,
                    "crumb" => $crumb,
                    "user" => $data,
                ],
            ]);
        }
    }

    /**
     * Checks if an email address is in use by someone or not.
     *
     * @param string $email
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function emailTaken(string $email): bool {
        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        $ct = $dbo->getRepository("Duppy\Entities\WebUser")->count([ 'email' => $email, ]);
        $ct += UserService::emailNeedsVerification($email);

        return $ct > 0;
    }

    /**
     * Checks if an email address is registered but needs verification.
     *
     * @param string $email
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function emailNeedsVerification(string $email): bool {
        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        $vCt = $dbo->getRepository("Duppy\Entities\WebUserVerification")->count([ 'email' => $email, ]);

        return $vCt > 0;
    }

    /**
     * Convenience function to get the current logged in user
     *
     * @return WebUser|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getLoggedInUser(): ?WebUser {
        $authToken = TokenManager::getAuthToken();

        if ($authToken == null || !array_key_exists("id", $authToken)) {
            return null;
        }

        return UserService::getUser($authToken["id"]);
    }

    /**
     * Gets the currently used EmailWhitelist class name, or null if its not enabled.
     *
     * @return ?string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getEmailWhitelist(): ?string {
        $whitelistClass = Settings::getSetting("auth.emailWhitelist");
        $subclass = is_subclass_of($whitelistClass, "Duppy\Abstracts\AbstractEmailWhitelist");

        return $subclass ? $whitelistClass : null;
    }

    /**
     * Returns if the user email is on the whitelist
     * @param string $email
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function emailWhitelisted(string $email): bool {
        $whitelistClass = UserService::getEmailWhitelist();

        // Whitelist not enabled
        if (!is_subclass_of($whitelistClass, "Duppy\Abstracts\AbstractEmailWhitelist")) {
            return true;
        }

        return $whitelistClass::check($email);
    }

    /**
     * Takes a string reference to a provider name and corrects it, and also checks if it is enabled.
     *
     * @param string $provider
     * @return boolean
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function enabledProvider(string &$provider): bool {
        if (!isset($provider) || empty($provider)) {
            $provider = "password";
        }

        return Settings::getSetting("auth.$provider.enable") == true;
    }

    /**
     * Authenticate with hybridauth, either directly or using a pre-given oAuth token
     *
     * @param string $provider
     * @param array|null $postArgs
     * @return Profile|string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function authenticateHybridAuth(string $provider, ?array $postArgs = []): Profile|string {
        $authHandler = Bootstrapper::getContainer()->get('authHandler');

        $oAuthToken = Util::indArrayNull($postArgs, "oAuthToken");
        $oAuthTokenSecret = Util::indArrayNull($postArgs, "oAuthTokenSecret");
        $refreshToken = Util::indArrayNull($postArgs, "refreshToken");
        $expiry = Util::indArrayNull($postArgs, "tokenExpiry");

        if ($authHandler::class !== "Hybridauth\Hybridauth") {
            return "Dependency error";
        }

        $adapter = $authHandler->getAdapter($provider);

        if ($oAuthToken !== null) {
            $adapter->setAccessToken([
                "expires_at" => $expiry,
                "access_token" => $oAuthToken,
                "access_token_secret" => $oAuthTokenSecret,
                "refresh_token" => $refreshToken,
            ]);
        } else {
            $authHandler->authenticate($provider);
            $connected = $authHandler->isConnectedWith($provider);

            if (!$connected) {
                return "Provider auth error";
            }
        }

        $profile = null;

        if ($adapter->isConnected()) {
            $profile = $adapter->getUserProfile();
        }

        return $profile;
    }

    /**
     * A unique 6-digit code for verifying registrations (or other things)
     *
     * @param $checker
     * @param int $loopProtection
     * @return ?int
     */
    public static function generateUniqueTempCode($checker, int $loopProtection = 0): ?int {
        if (++$loopProtection > 200) {
            return null;
        }

        try {
            $intGen = random_int(100000, 999999);
        } catch (Exception) {
            return null;
        }

        // elp
        if (!$checker($intGen)) {
            return UserService::generateUniqueTempCode($checker, $loopProtection);
        }

        return $intGen;
    }

    /**
     * Returns basic info of the user
     *
     * @param WebUser $user
     * @return array
     */
    public static function getBasicInfo(WebUser $user): array {
        $data = [
            "id" => $user->get("id"),
            "username" => $user->get("username"),
            "avatarUrl" => $user->get("avatarUrl"),
        ];

        if ($user->isMe()) {
            $merge = [
                "email" => $user->get("email"),
            ];

            $data = array_merge($data, $merge);
        }

        return $data;
    }

}