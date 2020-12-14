<?php

namespace Duppy\Middleware;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\Bootstrapper\Settings;
use Duppy\Bootstrapper\UserService;
use Duppy\Util;

class AuthRequiredSettingMiddleware extends AbstractRouteMiddleware {

    /**
     * Reject any using this middleware who isn't logged into a valid user
     * @throws DependencyException
     * @throws NotFoundException
     */
    final public function handle(): ?bool {
        // Only process this middleware if the setting is enabled
        if (!Settings::getSetting("requireAuthGeneralAccess")) {
            return true;
        }

        $user = UserService::getLoggedInUser();

        if ($user == null) {
            static::$response = Util::responseJSON(static::$response, [
                "success" => false,
                "data" => [],
                "err" => "You must be authenticated to view this content",
            ], 401);

            return false;
        }

        return true;
    }

}