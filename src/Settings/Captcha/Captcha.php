<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Captcha;

use Duppy\Abstracts\AbstractSetting;

class Captcha extends AbstractSetting {

    public static string $key = "captcha";

    public static string $description = "Captcha to enable (if any). Available are: hCaptcha";

    public static string $required = "string";

    public static bool $appSetting = true;

    public static $defaultValue = "";

}