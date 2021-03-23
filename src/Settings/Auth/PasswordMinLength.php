<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class PasswordMinLength extends AbstractSetting {

    public static string $key = "auth.password.minLength";

    public static string $description = "Minimum password length";

    public static string $required = "integer";

    public static bool $appSetting = true;

    public static $defaultValue = 6;

}