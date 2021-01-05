<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class SteamAuth extends AbstractSetting {

    public static string $key = "auth.steam.enable";

    public static string $description = "Enable steam authentication";

    public static string $required = "notnull|boolean";

    public static bool $appSetting = true;

    public static $defaultValue = false;

}
