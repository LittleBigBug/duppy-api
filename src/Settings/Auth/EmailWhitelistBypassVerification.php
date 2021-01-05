<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;


use Duppy\Abstracts\AbstractSetting;

class EmailWhitelistBypassVerification extends AbstractSetting {

    public static string $key = "auth.emailWhitelist.bypassVerification";

    public static string $description = "If email whitelist is on, this will allow any email on the whitelist to skip verification";

    public static string $required = "notnull|boolean";

    public static $defaultValue = "false";

}