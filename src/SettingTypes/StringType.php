<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\SettingTypes;

use Duppy\Abstracts\AbstractSettingType;
use JetBrains\PhpStorm\Pure;

class StringType extends AbstractSettingType {

    /**
     * @var string
     */
    public string $name = "string";

    /**
     * Cast value to string
     *
     * @param mixed $value
     * @return string
     */
    #[Pure]
    public function parse(mixed $value): string {
        return (string) $value;
    }

    /**
     * @param string $value
     * @return string
     */
    #[Pure]
    public function toStr(mixed $value): string {
        return (string) $value;
    }

    /**
     * Returns if the $value can be converted to a string
     *
     * @param mixed $value
     * @return bool
     */
    #[Pure]
    public function checkIsOfType(mixed $value): bool {
        $notArray = !is_array($value);
        $isObject = is_object($value);
        $setTypeCheck = !$isObject && settype($value, "string") !== false;
        $objectCheck = $isObject && method_exists($value, "__toString");

        return $notArray && ($setTypeCheck || $objectCheck);
    }

}