<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

use Duppy\Util;
use JetBrains\PhpStorm\Pure;

// Email whitelists where created for GoAirheads' need for no verification emails, however this can still
// be used for restricting domains or something (forcing @gmail, yahoo, known etc)
abstract class AbstractEmailWhitelist {

    protected static string $description = "";

    protected static ?array $emailListCache = null;

    protected static function genEmailList(): ?array {
        return null;
    }

    #[Pure]
    public static function getEmailList(): ?array {
        if (static::$emailListCache != null) {
            return static::$emailListCache;
        }

        return static::genEmailList();
    }

    #[Pure]
    public static function check(string $email): bool {
        $emailList = static::genEmailList();

        if ($emailList == null) {
            return false;
        }

        return Util::indArrayNull($emailList, $email) ?? false;
    }

    public static function getDescription(): string {
        return static::$description;
    }

}