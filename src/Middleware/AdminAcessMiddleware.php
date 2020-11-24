<?php

namespace Duppy\Middleware;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Util;

class AdminAcessMiddleware extends AbstractRouteMiddleware {

    /**
     * Reject any using this middleware who does not have admin permissions
     * @throws DependencyException
     * @throws NotFoundException
     */
    final public function handle() {
        $container = Bootstrapper::getContainer();

        $dbo = $container->get("database");
        $session = $container->get("session");

        $userid = $session->get("user");

        $respondFail = function(string $err = "unauthorized", int $status = 403) {
            static::$response = Util::responseJSON(static::$response, [
                "success" => false,
                "data" => [],
                "err" => "",
            ], $status);
        };

        if ($userid == null) {
            $respondFail(null, 401);
            return;
        }

        $user = $dbo->getRepository("Duppy\Entities\WebUser")->find($userid)->first();

        if ($user == null || !is_subclass_of($user, "Duppy\Entities\WebUser")) {
            $respondFail(null, 401);
            return;
        }

        if (!$user->hasPermission("admin")) {
            $respondFail(null, 403);
        }
    }

}