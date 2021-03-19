<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class EmailFromName extends AbstractSetting {

    public static string $key = "email.fromName";

    public static string $description = "Name to send emails from";

    public static string $required = "string";

    public static $defaultValue = "";

}