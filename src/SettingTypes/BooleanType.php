<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\SettingTypes;

use Duppy\Abstracts\AbstractSettingType;
use JetBrains\PhpStorm\Pure;

class BooleanType extends AbstractSettingType {

    /**
     * @var string
     */
    public string $name = "boolean";

    /**
     * @param mixed $value
     * @return bool
     */
    #[Pure]
    public function parse(mixed $value): bool {
        return boolval($value);
    }

    /**
     * @param bool $value
     * @return string
     */
    #[Pure]
    public function toStr(mixed $value): string {
        return $value === true ? "1" : "0";
    }

    /**
     * Returns if the $value is a bool
     *
     * @param mixed $value
     * @return bool
     */
    #[Pure]
    public function checkIsOfType(mixed $value): bool {
        return is_bool($value);
    }

}