<?php

namespace Duppy\Middleware;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\Settings;
use Duppy\Util;

class AuthRequiredSettingMiddleware extends AbstractRouteMiddleware {

    /**
     * Reject any using this middleware who isn't logged into a valid user
     * @throws DependencyException
     * @throws NotFoundException
     */
    final public function handle() {
        // Only process this middleware if the setting is enabled
        if (!Settings::getSetting("requireAuthGeneralAccess")) {
            return;
        }

        $user = Bootstrapper::getLoggedInUser();

        if ($user == null || !is_subclass_of($user, "Duppy\Entities\WebUser")) {
            static::$response = Util::responseJSON(static::$response, [
                "success" => false,
                "data" => [],
                "err" => "You must be authenticated to view this content",
            ], 401);
        }
    }

}