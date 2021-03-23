<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class VerifyAccountTemplate extends AbstractSetting {

    public static string $key = "email.verifyAccountTemplate";

    public static string $description = "Verification email template override";

    public static string $required = "string";

    public static $defaultValue = "";

}