<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Middleware;

use DI\DependencyException;
use DI\NotFoundException;
use GuzzleHttp\Exception\GuzzleException;
use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\DuppyException;
use Duppy\DuppyServices\Captcha;
use Duppy\Util;

class CaptchaMiddleware extends AbstractRouteMiddleware {

    /**
     * Captcha handling (general)
     *
     * @param callable $next
     * @return ?bool
     * @throws DependencyException
     * @throws DuppyException errType noneFound (setting function)
     * @throws GuzzleException
     * @throws NotFoundException
     */
    final public function handle(callable $next): ?bool {
        $captchaSrv = (new Captcha)->inst();
        $captchaResponse = static::$request->getHeader("X-Captcha-Response");

        $success = $captchaSrv->verify($captchaResponse[0]);

        if ($success) {
            static::$response = Util::responseError(static::$response, "Captcha failed", 401);
            return false;
        }

        return true;
    }

}