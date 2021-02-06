<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\SettingTypes;

use Cassandra\Date;
use Duppy\DuppyException;
use Duppy\Enum\DuppyError;
use Duppy\Util;
use Exception;
use DateInterval;
use Duppy\Abstracts\AbstractSettingType;
use JetBrains\PhpStorm\Pure;

class DateIntervalType extends AbstractSettingType {

    /**
     * @var string
     */
    public string $name = "dateInterval";

    /**
     * @param mixed $value
     * @return mixed
     * @throws Exception
     */
    #[Pure]
    public function parse(mixed $value): DateInterval {
        return new DateInterval($value);
    }

    /**
     * @param DateInterval $value
     * @return string
     * @throws DuppyException ErrType incorrectType if $value is not a DateInterval object
     */
    #[Pure]
    public function toStr(mixed $value): string {
        if (!$this->checkIsOfType($value)) {
            throw new DuppyException(DuppyError::incorrectType());
        }

        return $value->format("%y years %m months %d days %h hours %s seconds");
    }

    /**
     * @param mixed $value
     * @return bool
     */
    #[Pure]
    public function checkIsOfType(mixed $value): bool {
        return Util::is($value, DateInterval::class);
    }

}