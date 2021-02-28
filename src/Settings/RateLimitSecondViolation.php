<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class RateLimitSecondViolation extends AbstractSetting {

    public static string $key = "rateLimit.secondViolation";

    public static string $description = "How long to ban an IP (minutes) for exceeding the per second rate limit. -1 to disable and 0 for permanent";

    public static string $required = "notnull|integer";

    public static $defaultValue = 10;

}