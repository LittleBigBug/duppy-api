<?php

namespace Duppy\Middleware;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Util;

class AdminAccessMiddleware extends AbstractRouteMiddleware {

    /**
     * Reject any using this middleware who does not have admin permissions
     * @throws DependencyException
     * @throws NotFoundException
     */
    final public function handle(): ?bool {
        $user = Bootstrapper::getLoggedInUser();

        if ($user == null || !is_subclass_of($user, "Duppy\Entities\WebUser")) {
            static::$response = static::$response->withStatus(401);
            return false;
        }

        if (!$user->hasPermission("admin")) {
            static::$response = static::$response->withStatus(403);
            return false;
        }
    }

}