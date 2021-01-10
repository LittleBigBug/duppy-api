<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Builders;

use Duppy\Abstracts\AbstractFileBuilder;
use Duppy\Abstracts\AbstractSetting;
use Duppy\Abstracts\AbstractSettingType;
use Duppy\DuppyServices\Settings;
use Duppy\Util;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

/**
 * Class SettingsBuilder
 * @package Duppy\Builders
 */
final class SettingsBuilder extends AbstractFileBuilder {

    /**
     * Path of setting types to build
     *
     * @var string
     */
    public string $settingTypesSrc = "";

    /**
     * Settings builder constructor.
     *
     * @param string $settingsSrc
     * @param string|null $settingTypesSrc
     */
    public function __construct(string $settingsSrc = "Settings", ?string $settingTypesSrc = null) {
        parent::__construct($settingsSrc);

        // Assumes settingsSrc is "Settings" -> "SettingTypes"
        $backupTypeSrc = substr($settingsSrc, 0, strlen($settingsSrc - 1)) . "Types";
        $this->settingTypesSrc = $settingTypesSrc ?? $backupTypeSrc;
    }

    /**
     * Build settings
     */
    public function build() {
        $this->buildSettingDefinitions();
        $this->buildSettingTypes();
    }

    private function buildSettingDefinitions() {
        $settingsMngr = (new Settings)->inst();

        $callback = function (string $class, string $path) use ($settingsMngr) {
            $key = $class::$key;

            if (!isset($key)) {
                return;
            }

            if ($class::$appSetting) {
                $settingsMngr->addAppSetting($key);
            }

            $settingsMngr->addSetting($key, $class);
        };

        $filter = function (string $className, string $path): bool {
            return is_subclass_of($className, AbstractSetting::class);
        };

        $this->directoryIterator(true, $callback, $filter);
    }

    private function buildSettingTypes() {
        $settingsMngr = (new Settings)->inst();

        $callback = function (string $class, string $path) use ($settingsMngr) {
            $name = $class::$name;

            if (!isset($name)) {
                return;
            }

            $settingsMngr->addSettingType($name, $class);
        };

        $filter = function (string $className, string $path): bool {
            return is_subclass_of($className, AbstractSettingType::class);
        };

        $this->directoryIterator(true, $callback, $filter);
    }

}