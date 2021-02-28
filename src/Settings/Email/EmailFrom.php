<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class EmailFrom extends AbstractSetting {

    public static string $key = "email.from";

    public static string $description = "Email address to send emails from";

    public static string $required = "string";

    public static $defaultValue = "";

}