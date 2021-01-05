<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class EmailWhitelistRequiredRegister extends AbstractSetting {

    public static string $key = "auth.emailWhitelist.requiredRegister";

    public static string $description = "If email whitelist is on, this will require that the email is on that list in order to register";

    public static string $required = "notnull|boolean";

    public static $defaultValue = "false";

}