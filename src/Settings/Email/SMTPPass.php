<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class SMTPPass extends AbstractSetting {

    public static string $key = "email.smtp.password";

    public static string $description = "SMTP Password";

    public static string $required = "string";

    public static $defaultValue = "";

}