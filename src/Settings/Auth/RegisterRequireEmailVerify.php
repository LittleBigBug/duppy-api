<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

class RegisterRequireEmailVerify {

    public static string $key = "auth.registerRequireEmailVerify";

    public static string $description = "(Recommended) Require the user to verify their email before logging in? (Requires email system)";

    public static string $required = "notnull|boolean";

    public static $defaultValue = false;

}