<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class PermaGlobalBan extends AbstractSetting {

    public static string $key = "permaGlobalBan";

    public static string $description = "Do perma-bans in any environment automatically globalban?";

    public static string $required = "notnull|boolean";

    public static $defaultValue = true;

}