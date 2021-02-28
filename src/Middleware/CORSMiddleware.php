<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Middleware;

use Duppy\Abstracts\AbstractRouteMiddleware;

class CORSMiddleware extends AbstractRouteMiddleware {

    /**
     * Access Control
     *
     * Allow origin from all and allowed headers
     *
     * @param callable $next
     * @return bool|null
     */
    final public function handle(callable $next): ?bool {
        $next();

        self::$response = self::$response
            ->withHeader("Access-Control-Allow-Origin", "*")
            ->withHeader("Access-Control-Allow-Methods", "GET,HEAD,OPTIONS,POST,PUT")
            ->withHeader("Access-Control-Allow-Headers",
                "Access-Control-Allow-Headers, Origin, Accept, X-Requested-With, X-Captcha-Response, X-Client-Id,
                Authorization, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers");

        return null;
    }

}
