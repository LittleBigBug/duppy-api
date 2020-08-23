<?php

namespace Duppy\Abstracts;

abstract class AbstractSetting {

    /**
     * Key, a unique value separated by periods to denote categories and sub-categories
     *
     * @var string
     */
    public static string $key;

    /**
     * Setting description
     *
     * @var string
     */
    public static string $description = "";

    /**
     * Setting value requirements
     * Similar to pterodactyl settings, separated by |
     *
     * Possible values:
     * notnull, integer, string, boolean, max:[num]
     *
     * @var string
     */
    public static string $required = "";

    /**
     * Default fallback value
     */
    public static $default;

}
