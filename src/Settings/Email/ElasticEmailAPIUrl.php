<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Settings\Email;

use Duppy\Abstracts\AbstractSetting;

class ElasticEmailAPIUrl extends AbstractSetting {

    public static string $key = "elasticEmail.url";

    public static string $description = "Elasticemail custom api URL";

    public static string $required = "string";

    public static $defaultValue = "https://api.elasticemail.com";

}