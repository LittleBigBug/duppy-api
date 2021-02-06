<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class SMTPUser extends AbstractSetting  {

    public static string $key = "email.smtp.username";

    public static string $description = "SMTP Username";

    public static string $required = "string";

    public static $defaultValue = "";

}