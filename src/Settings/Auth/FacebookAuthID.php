<?php

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class FacebookAuthID extends AbstractSetting {

    public static string $key = "auth.facebook.id";

    public static string $description = "Facebook authentication app ID";

    public static string $required = "notnull|string";

    public static $defaultValue = "";

}