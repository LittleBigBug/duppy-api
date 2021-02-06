<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class SMTPHost extends AbstractSetting {

    public static string $key = "email.smtp.host";

    public static string $description = "SMTP Server hostname";

    public static string $required = "string";

    public static $defaultValue = "";

}