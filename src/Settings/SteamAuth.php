<?php

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class SteamAuth extends AbstractSetting {

    public static string $key = "auth.steam.enable";

    public static string $description = "Enable steam authentication";

    public static string $required = "notnull|boolean";

    public static $defaultValue = false;

}
