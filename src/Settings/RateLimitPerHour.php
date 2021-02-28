<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class RateLimitPerHour extends AbstractSetting {

    public static string $key = "rateLimit.perHour";

    public static string $description = "Default per-hour limit for clients to be rate limited";

    public static string $required = "notnull|integer";

    public static $defaultValue = 5000;

}