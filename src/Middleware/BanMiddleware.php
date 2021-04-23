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
use Duppy\DuppyServices\UserService;
use Duppy\Util;

class BanMiddleware extends AbstractRouteMiddleware {

    /**
     * @param callable $next
     * @return bool|null
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function handle(callable $next): ?bool {
        $userService = (new UserService)->inst();
        $user = $userService->getLoggedInUser();

        if ($user == null) {
            return true;
        }

        if ($user->banned()) {
            $bans = $user->getActiveBans();
            $unbanTime = $user->unbanTime();

            static::$response = Util::responseJSON(static::$response, [
                "success" => false,
                "data" => [
                    "banned" => true,
                    "bans" => $bans,
                    "unbanTime" => $unbanTime,
                ],
                "err" => "You are banned and not allowed to access this",
            ], 401);

            return false;
        }

        return true;
    }

}