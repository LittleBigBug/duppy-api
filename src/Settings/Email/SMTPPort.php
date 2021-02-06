<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class SMTPPort extends AbstractSetting {

    public static string $key = "email.smtp.port";

    public static string $description = "SMTP Server port";

    public static string $required = "integer";

    public static $defaultValue = 587;

}