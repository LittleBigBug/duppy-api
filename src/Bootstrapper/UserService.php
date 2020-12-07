<?php

namespace Duppy\Bootstrapper;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Entities\WebUser;
use Duppy\Util;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\User\Profile;
use Slim\Psr7\Response;

final class UserService {

    /**
     * Convenience function to get a user by their ID
     *
     * @param $id
     * @return WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getUser($id = null): WebUser {
        if ($id == "me" || $id == null) {
            return static::getLoggedInUser();
        }

        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        return $dbo->getRepository("Duppy\Entities\WebUser")->find($id)->first();
    }

    /**
     * Convenience function to get a user by their Username
     *
     * @param string $username
     * @return WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getUserByName(string $username): WebUser {
        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        return $dbo->getRepository("Duppy\Entities\WebUser")->findBy([ "username" => $username ])->first();
    }

    /**
     * Convenience function to get a user by their Email
     *
     * @param string $email
     * @return WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getUserByEmail(string $email): WebUser {
        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        return $dbo->getRepository("Duppy\Entities\WebUser")->findBy([ "email" => $email ])->first();
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

        return static::getUser($authToken["id"]);
    }

    /**
     * Takes a string reference to a provider name and corrects it, and also checks if it is enabled.
     *
     * @param string $provider
     * @return boolean
     */
    public static function enabledProvider(string &$provider): bool {
        if (!isset($provider) || empty($provider)) {
            $provider = "password";
        }

        $providerEnabled = Settings::getSetting("auth.$provider.enable") == true;
        return $providerEnabled;
    }

    /**
     * Authenticate with hybridauth, either directly or using a pre-given oAuth token
     *
     * @param string $provider
     * @param array|null $postArgs
     * @return Profile|string
     * @throws DependencyException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public static function authenticateHybridAuth(string $provider, ?array $postArgs = []) {
        $authHandler = Bootstrapper::getContainer()->get('authHandler');

        $oAuthToken = $postArgs["oAuthToken"];
        $oAuthTokenSecret = $postArgs["oAuthTokenSecret"];
        $refreshToken = $postArgs["refreshToken"];
        $expiry = $postArgs["tokenExpiry"];

        if (!is_subclass_of($authHandler, "Hybridauth\Hybridauth")) {
            return "Provider auth error";
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
            $connected = $authHandler->isConnected();

            if (!$connected) {
                return "Provider auth error";
            }
        }

        return $adapter->getUserProfile();
    }

}