<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class ForgotPasswordTemplate extends AbstractSetting {

    public static string $key = "email.forgotPasswordTemplate";

    public static string $description = "Forgot Password email template override";

    public static string $required = "string";

    public static $defaultValue = "";

}