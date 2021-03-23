<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class PasswordMinLowercase extends AbstractSetting {

    public static string $key = "auth.password.minLowercase";

    public static string $description = "Minimum amount of lower case characters in passwords";

    public static string $required = "integer";

    public static bool $appSetting = true;

    public static $defaultValue = 1;

}