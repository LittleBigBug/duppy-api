<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class GoogleAuth extends AbstractSetting {

    public static string $key = "auth.google.enable";

    public static string $description = "Enable google authentication";

    public static string $required = "notnull|boolean";

    public static bool $appSetting = true;

    public static $defaultValue = false;

}