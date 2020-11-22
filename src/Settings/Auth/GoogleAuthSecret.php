<?php

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class GoogleAuthSecret extends AbstractSetting {

    public static string $key = "auth.google.secret";

    public static string $description = "Google authentication secret";

    public static string $required = "notnull|string";

    public static $defaultValue = "";

}