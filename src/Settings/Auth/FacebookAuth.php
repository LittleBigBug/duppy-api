<?php

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class FacebookAuth extends AbstractSetting {

    public static string $key = "auth.facebook.enable";

    public static string $description = "Enable facebook authentication";

    public static string $required = "notnull|boolean";

    public static bool $appSetting = true;

    public static $defaultValue = false;

}