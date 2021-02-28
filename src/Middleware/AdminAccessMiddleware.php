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
use Duppy\DuppyServices\UserService;

class AdminAccessMiddleware extends AbstractRouteMiddleware {

    /**
     * Reject any using this middleware who does not have admin permissions
     * @param callable $next
     * @return bool|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    final public function handle(callable $next): ?bool {
        $user = (new UserService)->inst()->getLoggedInUser();

        if ($user == null) {
            static::$response = static::$response->withStatus(401);
            return false;
        }

        if (!$user->hasPermission("admin")) {
            static::$response = static::$response->withStatus(403);
            return false;
        }

        return true;
    }

}