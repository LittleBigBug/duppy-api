<?php

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class RequireGeneralAccessAuth extends AbstractSetting {

    public static string $key = "requireAuthGeneralAccess";

    public static string $description = "Restrict most of the public API calls to logged in users only [Ex, you can use the API to login but not to view a users profile until you are logged in]";

    public static string $required = "notnull|boolean";

    public static bool $appSetting = true;

    public static $defaultValue = false;

}