<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class PasswordResetExpire extends AbstractSetting {

    public static string $key = "auth.password.expire";

    public static string $description = "How long until a password reset request expires";

    public static string $required = "notnull|dateInterval";

    public static $defaultValue = "2H";

}