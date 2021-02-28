<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Captcha;

use Duppy\Abstracts\AbstractSetting;

class hCaptchaSecret extends AbstractSetting {

    public static string $key = "hCaptcha.private";

    public static string $description = "Private key for hCaptcha.";

    public static string $required = "string";

    public static $defaultValue = "";

}