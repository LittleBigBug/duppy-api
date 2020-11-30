<?php

namespace Duppy\Middleware;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Util;

class AdminAccessMiddleware extends AbstractRouteMiddleware {

    final static private function respondFail(string $err = "Unauthorized", int $status = 403) {
        static::$response = Util::responseJSON(static::$response, [
            "success" => false,
            "data" => [],
            "err" => $err,
        ], $status);
    }

    /**
     * Reject any using this middleware who does not have admin permissions
     * @throws DependencyException
     * @throws NotFoundException
     */
    final public function handle() {
        $user = Bootstrapper::getLoggedInUser();

        if ($user == null || !is_subclass_of($user, "Duppy\Entities\WebUser")) {
            static::respondFail(null, 401);
            return;
        }

        if (!$user->hasPermission("admin")) {
            static::respondFail(null, 403);
        }
    }

}