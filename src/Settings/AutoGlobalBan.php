<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class AutoGlobalBan extends AbstractSetting {

    public static string $key = "autoGlobalBan";

    public static string $description = "How many active environment bans would get a user auto global-banned?";

    public static string $required = "notnull|integer";

    public static $defaultValue = 2;

}