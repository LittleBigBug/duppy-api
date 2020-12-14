<?php

namespace Duppy\Settings;

use Duppy\Abstracts\AbstractSetting;

class EqualWeightPasses extends AbstractSetting {

    public static string $key = "equalWeightPasses";

    public static string $description = "When running weight checks, can users of equal weight affect each other?";

    public static string $required = "notnull|boolean";

    public static $defaultValue = false;

}