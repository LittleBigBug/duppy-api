<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class EmailEngine extends AbstractSetting {

    public static string $key = "email.engine";

    public static string $description = "What email engine to use (default 'smtp')";

    public static string $required = "boolean";

    public static $defaultValue = 'smtp';

}