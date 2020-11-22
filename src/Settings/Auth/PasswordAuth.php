<?php

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class PasswordAuth extends AbstractSetting {

    public static string $key = "auth.password.enable";

    public static string $description = "Enable default email/username and password authentication publicly. (Admins can always log in using their credentials)";

    public static string $required = "notnull|boolean";

    public static bool $appSetting = true;

    public static $defaultValue = false;

}
