<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\SettingTypes;

use Duppy\Abstracts\AbstractSettingType;
use Duppy\DuppyException;
use Duppy\Enum\DuppyError;
use JetBrains\PhpStorm\Pure;

class JsonType extends AbstractSettingType {

    /**
     * @param mixed $value
     * @return string
     */
    #[Pure]
    public function parse(mixed $value): mixed {
        return json_decode($value);
    }

    /**
     * @param mixed $value
     * @return string
     * @throws DuppyException ErrType incorrectType if the json encode returns false
     */
    #[Pure]
    public function toStr(mixed $value): string {
        $res = json_encode($value);

        if ($res === false) {
            throw new DuppyException(DuppyError::incorrectType());
        }

        return $res;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    #[Pure]
    public function checkIsOfType(mixed $value): bool {
        $implements = class_implements($value);
        return isset($implements["JsonSerializable"]);
    }

}