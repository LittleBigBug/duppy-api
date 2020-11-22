<?php

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class AppTitle extends AbstractSetting {

    public static string $key = "title";

    public static string $description = "Application's Title";

    public static string $required = "notnull|string";

    public static bool $appSetting = true;

    public static $defaultValue = "Duppy";

}