<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Auth;

use Duppy\Abstracts\AbstractSetting;

class EmailVerificationRequired extends AbstractSetting {

    public static string $key = "auth.emailVerificationReq";

    public static string $description = "When signing up with an email, is it required to verify it? (recommended, requires email.useEmail) (required for allowing users to add emails)";

    public static string $required = "boolean";

    public static $defaultValue = false;

}