<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class UseEmail extends AbstractSetting {

    public static string $key = "email.enable";

    public static string $description = "Enable (use) the email system.";

    public static string $required = "boolean";

    public static $defaultValue = false;

}