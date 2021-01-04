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
use Duppy\Bootstrapper\EnvironmentService;

class EnvironmentMiddleware extends AbstractRouteMiddleware {

    /**
     * @return bool|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function handle(): ?bool {
        $environmentStrs = static::$request->getHeader("X-Environment");

        if (count($environmentStrs) > 1) {
            static::$response = static::$response->withStatus(400);
            return false;
        }

        $envStr = $environmentStrs[0];
        $environment = EnvironmentService::checkEnvironment($envStr);

        if ($environment == false) {
            static::$response = static::$response->withStatus(400);
            return false;
        }

        static::$request = static::$request->withAttribute("environment", $environment);
        EnvironmentService::setEnvironment($environment);
    }

}