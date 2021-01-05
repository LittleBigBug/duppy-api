<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class EmailWhitelist extends AbstractSetting {

    public static string $key = "auth.emailWhitelist";

    public static string $description = "Email Whitelist class to use. (Restricts what emails can be used to sign up)";

    public static string $required = "string";

    public static $defaultValue = "";

}