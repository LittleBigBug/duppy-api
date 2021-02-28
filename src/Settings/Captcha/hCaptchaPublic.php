<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Captcha;

use Duppy\Abstracts\AbstractSetting;

class hCaptchaPublic extends AbstractSetting {

    public static string $key = "hCaptcha.public";

    public static string $description = "Public key for hCaptcha.";

    public static string $required = "string";

    public static bool $appSetting = true;

    public static $defaultValue = "";

}