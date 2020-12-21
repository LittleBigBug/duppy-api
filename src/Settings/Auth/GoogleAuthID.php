<?php

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class GoogleAuthID extends AbstractSetting {

    public static string $key = "auth.google.id";

    public static string $description = "Google authentication app ID";

    public static string $required = "notnull|string";

    public static bool $appSetting = true;

    public static $defaultValue = "";

}