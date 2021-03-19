<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class EmailReplyToName extends AbstractSetting {

    public static string $key = "email.replyToName";

    public static string $description = "Reply to email name (optional)";

    public static string $required = "string";

    public static $defaultValue = "";

}