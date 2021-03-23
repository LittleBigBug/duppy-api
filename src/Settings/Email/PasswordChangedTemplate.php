<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class PasswordChangedTemplate extends AbstractSetting {

    public static string $key = "email.passwordChangedTemplate";

    public static string $description = "Password Reset email template override";

    public static string $required = "string";

    public static $defaultValue = "";

}