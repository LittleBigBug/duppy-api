<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class FacebookAuthSecret extends AbstractSetting {

    public static string $key = "auth.facebook.secret";

    public static string $description = "Facebook authentication secret";

    public static string $required = "notnull|string";

    public static $defaultValue = "";

}