<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Middleware;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\DuppyException;
use Duppy\DuppyServices\Settings;
use Duppy\DuppyServices\UserService;
use Duppy\Util;

class AuthRequiredSettingMiddleware extends AbstractRouteMiddleware {

    /**
     * Reject any using this middleware who isn't logged into a valid user
     *
     * @param callable $next
     * @return ?bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    final public function handle(callable $next): ?bool {
        // Only process this middleware if the setting is enabled
        $settingsMngr = (new Settings)->inst();

        if (!$settingsMngr->getSetting("requireAuthGeneralAccess")) {
            return true;
        }

        $userService = (new UserService)->inst();
        $user = $userService->getLoggedInUser();

        if ($user == null) {
            static::$response = Util::responseError(static::$response, "You must be authenticated to access this", 401);
            return false;
        }

        return true;
    }

}