<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class ClientURL extends AbstractSetting {

    public static string $key = "clientUrl";

    public static string $description = "URL of the main http client application associated with the API";

    public static string $required = "string";

    public static $defaultValue = "";

}