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
use Duppy\DuppyServices\Settings;
use Duppy\Util;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class captchaMiddleware extends AbstractRouteMiddleware {

    /**
     * hCaptcha verify
     */
    const siteVerifyUrl = "https://hcaptcha.com/siteverify";

    /**
     * Reject any using this middleware who isn't logged into a valid user
     * @return ?bool
     * @throws NotFoundException
     * @throws GuzzleException
     * @throws DependencyException
     * @throws DuppyException errType noneFound (setting function)
     */
    final public function handle(): ?bool {
        // Only process this middleware if the setting is enabled
        $settingsMngr = (new Settings)->inst();

        $stgs = $settingsMngr->getSettings([
            "hCaptcha.enabled", "hCaptcha.private",
        ]);

        if (!Util::indArrayNull($stgs, "hCaptcha.enabled")) {
            return true;
        }

        $captchaResponse = static::$request->getHeader("X-Captcha-Response");
        $private = Util::indArrayNull($stgs, "hCaptcha.private");

        $client = new Client;
        $res = $client->request("POST", self::siteVerifyUrl, [
            "form_params" => [
                "secret" => "my-secret $private",
                "response" => $captchaResponse,
            ],
        ]);

        $body = $res->getBody()->getContents();
        $json = json_decode($body);

        if (!$json->success) {
            static::$response = Util::responseJSON(static::$response, [
                "success" => false,
                "data" => [ ],
                "err" => "Captcha failed",
            ], 401);

            return false;
        }

        return true;
    }

}