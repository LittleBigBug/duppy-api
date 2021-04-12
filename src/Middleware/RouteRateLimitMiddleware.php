<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Middleware;

use Duppy\Abstracts\AbstractRouteMiddleware;

class RouteRateLimitMiddleware extends AbstractRouteMiddleware {

    public function handle(callable $next): ?bool {
        // TODO: Implement handle() method.
        return true;
    }

}