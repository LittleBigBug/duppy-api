<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

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
     * Setting category separated by .
     *
     * @var string
     */
    public static string $category = "system.uncategorized";

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
     * Setting that is sent upfront to the user
     *
     * @var bool
     */
    public static bool $appSetting = false;

    /**
     * Default fallback value
     */
    public static $defaultValue;

}
