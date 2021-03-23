<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class PasswordMinSpecial extends AbstractSetting {

    public static string $key = "auth.password.minSpecial";

    public static string $description = "Minimum amount of special characters in passwords";

    public static string $required = "integer";

    public static bool $appSetting = true;

    public static $defaultValue = 1;

}