<?php

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class PasswordAuth extends AbstractSetting {

    public static string $key = "auth.password.enable";

    public static string $description = "Enable default email/username and password authentication";

    public static string $required = "notnull|boolean";

    public static $defaultValue = false;

}
