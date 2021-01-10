<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

use Duppy\DuppyException;
use Duppy\Enum\DuppyError;
use JetBrains\PhpStorm\Pure;

abstract class AbstractSettingType {

    /**
     * Type name
     * @var string
     */
    public string $name;

    /**
     * Parse a mixed value into the type that PHP can understand
     * $value is usually a string that represents the real type
     *
     * @param mixed $value
     * @return mixed
     */
    #[Pure]
    abstract public function parse(mixed $value): mixed;

    /**
     * Convert $value to a string assuming the $value is of the class's type
     *
     * @param mixed $value
     * @return string
     */
    abstract public function toStr(mixed $value): string;

    /**
     * Checks the mixed value before attempting to convert.
     *
     * @param mixed $value
     * @return string
     * @throws DuppyException ErrType incorrectType if the $value is not compatible with this setting's type
     */
    #[Pure]
    public function store(mixed $value): string {
        $canConvert = $this->checkIsOfType($value);

        if (!$canConvert) {
            throw new DuppyException(DuppyError::incorrectType());
        }

        return $this->toStr($value);
    }

    /**
     * Check if the value can be parsed to the type
     *
     * @param mixed $value
     * @return bool
     */
    #[Pure]
    abstract public function checkIsOfType(mixed $value): bool;

    /**
     * Invoke the class directly to parse it (shortcut)
     *
     * @param mixed $value
     * @return mixed
     */
    #[Pure]
    public function __invoke(mixed $value): mixed {
        return $this->parse($value);
    }

}