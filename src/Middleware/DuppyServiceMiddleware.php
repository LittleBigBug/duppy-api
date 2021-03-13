<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Middleware;

use Duppy\Util;
use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\Abstracts\AbstractService;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\Dependency;
use Duppy\DuppyServices\TokenManager;

/**
 * Duppy API specific middleware calls
 *
 * Class DuppyServiceMiddleware
 * @package Duppy\Middleware
 */
class DuppyServiceMiddleware extends AbstractRouteMiddleware {

    /**
     * @param callable $next
     * @return bool|null
     */
    public function handle(callable $next): ?bool {
        Bootstrapper::setCurrentRequest(static::$request);

        // If the request has APIClient headers but resolves to no APIClient reject with an error
        if (static::$request->hasHeader("X-Client-ID")) {
            $apiClient = (new TokenManager)->inst()->getAPIClient();

            if ($apiClient == null) {
                Util::responseError(static::$response, "Malformed/incorrect APIClient Credentials", 400);
                return false;
            }
        }

        $next();

        AbstractService::CleanServices();
        Dependency::refreshInjected();
        return null;
    }

}