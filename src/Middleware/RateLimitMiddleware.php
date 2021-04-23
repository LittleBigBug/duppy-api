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
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyException;
use Duppy\DuppyServices\Settings;
use Duppy\Util;
use PalePurple\RateLimit\RateLimit;

class RateLimitMiddleware extends AbstractRouteMiddleware {

    /**
     * @param callable $next
     * @return bool|null
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function handle(callable $next): ?bool {
        $adapter = Bootstrapper::getContainer()->get("rateLimitAdapter");

        // Todo please cache
        $rateLimitStgs = (new Settings)->inst()->getSettings([
            "rateLimit.perSecond", "rateLimit.perMinute",
            "rateLimit.perHour", "rateLimit.secondViolation",
        ]);

        $psLimitDef = new RateLimit("ps", $rateLimitStgs["rateLimit.perSecond"], 1, $adapter);
        $pmLimitDef = new RateLimit("pm", $rateLimitStgs["rateLimit.perMinute"], 60, $adapter);
        $phLimitDef = new RateLimit("ph", $rateLimitStgs["rateLimit.perHour"], 3600, $adapter);

        $ip = static::$request->getAttribute("ip_address");
        $hashIP = hash("sha256", $ip); // Use a hash of the IP instead
        
        $rateLimit = function () {
            static::$response = Util::responseError(static::$response, "You are being rate limited.", 429);
            return false;
        };

        if (!$psLimitDef->check($hashIP)) {
            return $rateLimit();
        }

        if (!$pmLimitDef->check($hashIP)) {
            return $rateLimit();
        }

        if (!$phLimitDef->check($hashIP)) {
            return $rateLimit();
        }

        return true;
    }

}