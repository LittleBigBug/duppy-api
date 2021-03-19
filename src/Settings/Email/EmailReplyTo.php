<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class EmailReplyTo extends AbstractSetting {

    public static string $key = "email.replyTo";

    public static string $description = "Reply to email (optional)";

    public static string $required = "string";

    public static $defaultValue = "";

}