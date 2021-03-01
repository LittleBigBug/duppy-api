<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Middleware;

use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\Abstracts\AbstractService;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\Dependency;

class DuppyServiceMiddleware extends AbstractRouteMiddleware {

    /**
     * @param callable $next
     * @return bool|null
     */
    public function handle(callable $next): ?bool {
        Bootstrapper::setCurrentRequest(static::$request);

        $next();

        AbstractService::CleanServices();
        Dependency::refreshInjected();
        return null;
    }

}