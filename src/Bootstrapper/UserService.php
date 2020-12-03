<?php

namespace Duppy\Bootstrapper;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Entities\WebUser;

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

}