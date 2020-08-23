<?php

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class SteamAuthSecret extends AbstractSetting {

    public static string $key = "auth.steam.secret";

    public static string $description = "Steam OpenID secret";

    public static string $required = "notnull|string";

    public static $default = "";

}
