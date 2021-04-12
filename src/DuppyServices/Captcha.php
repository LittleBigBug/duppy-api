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
use Duppy\Bootstrapper\DCache;
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
     * @var DCache
     */
    protected DCache $settingsCache;

    /**
     * Captcha constructor.
     * @param bool $singleton
     */
    public function __construct(bool $singleton = false) {
        $this->settingsCache = new DCache;
        parent::__construct($singleton);
    }

    /**
     * Returns if any captcha service is enabled
     *
     * @return array
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function getSettings(): array {
        if (($settings = $this->settingsCache->get()) != null) {
            return $settings;
        }

        $settingsMngr = (new Settings)->inst();
        $settings = $settingsMngr->getSettings([
            "captcha",
            "hCaptcha.private",
        ]);

        $anyEnabled = !empty($settings["captcha"]);
        $settings["captchaEnabled"] = $anyEnabled;

        return $this->settingsCache->setObject($settings);
    }

    /**
     * if any captcha is enabled
     *
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function isEnabled(): bool {
        $settings = $this->getSettings();
        return $settings["captchaEnabled"] == true;
    }

    /**
     * Verify the currently enabled captcha (if any) and returns its success
     *
     * @param string $response = ""
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws GuzzleException
     * @throws NotFoundException
     */
    public function verify(string $response = ""): bool {
        // Only process this middleware if the setting is enabled
        if (!$this->isEnabled()) {
            return true;
        }

        if (empty($response)) {
            return false;
        }

        $settings = $this->getSettings();
        $use = strtolower($settings["captcha"]);

        return match ($use) {
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
        $settings = $this->getSettings();
        $privateKey = $settings["hCaptcha.private"];

        if (empty($privateKey)) {
            throw new DuppyException(DuppyError::missingSetting());
        }

        $client = new Client;
        $res = $client->post(self::hCaptchaVerifyUrl, [
            "form_params" => [
                "secret" => $privateKey,
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