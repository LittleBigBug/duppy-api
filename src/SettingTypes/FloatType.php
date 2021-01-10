<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\SettingTypes;

use Duppy\Abstracts\AbstractSettingType;
use JetBrains\PhpStorm\Pure;

class FloatType extends AbstractSettingType {

    /**
     * @var string
     */
    public string $name = "float";

    /**
     * @param mixed $value
     * @return int
     */
    #[Pure]
    public function parse(mixed $value): int {
        return floatval($value);
    }

    /**
     * @param float $value
     * @return string
     */
    #[Pure]
    public function toStr(mixed $value): string {
        return (string) $value;
    }

    /**
     * Returns if the $value is a float or integer
     *
     * @param mixed $value
     * @return bool
     */
    #[Pure]
    public function checkIsOfType(mixed $value): bool {
        return is_int($value) || is_float($value);
    }

}