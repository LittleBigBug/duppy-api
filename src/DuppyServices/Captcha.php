<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\DuppyServices;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractService;
use Duppy\DuppyException;
use Duppy\Enum\DuppyError;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service to handle captcha stuff
 *
 * For now, hCaptcha is used as a good privacy centred captcha and is the only one used.
 * This should be refactored or added into a type-system (defining classes for each type, ie file builder)
 * So support for external captchas can be added if needed. ex Google reCaptcha, as it will never be officially supported.
 *
 * Class Captcha
 * @package Duppy\DuppyServices
 */
class Captcha extends AbstractService {

    /**
     * hCaptcha verify
     */
    const hCaptchaVerifyUrl = "https://hcaptcha.com/siteverify";

    /**
     * Verify the currently enabled captcha (if any) and returns its success
     *
     * @param string $response
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws GuzzleException
     * @throws NotFoundException
     */
    public function verify(string $response): bool {
        // Only process this middleware if the setting is enabled
        $settingsMngr = (new Settings)->inst();
        $captcha = $settingsMngr->getSetting("Captcha");

        if (empty($captcha)) {
            return true;
        }

        return match (strtolower($captcha)) {
            "hcaptcha" => $this->hCaptcha($response),
            default => false,
        };
    }

    /**
     * Handle hCaptcha
     *
     * @param string $captchaResp
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws GuzzleException
     * @throws NotFoundException
     */
    public function hCaptcha(string $captchaResp): bool {
        $settingsMngr = (new Settings)->inst();
        $privateKey = $settingsMngr->getSetting("hCaptcha.private");

        if (empty($privateKey)) {
            throw new DuppyException(DuppyError::missingSetting());
        }

        $client = new Client;
        $res = $client->request("POST", self::hCaptchaVerifyUrl, [
            "form_params" => [
                "secret" => "my-secret $privateKey",
                "response" => $captchaResp,
            ],
        ]);

        $body = $res->getBody()->getContents();
        $json = json_decode($body);

        if (isset($json->success)) {
            return $json->success;
        }

        return false;
    }

}