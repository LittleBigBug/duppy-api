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
use JetBrains\PhpStorm\Pure;

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
    #[Pure]
    public function __construct(string $settingsSrc = "Settings", ?string $settingTypesSrc = null) {
        parent::__construct($settingsSrc);

        // Assumes settingsSrc is "Settings" -> "SettingTypes"
        $backupTypeSrc = substr($settingsSrc, 0, strlen($settingsSrc - 1)) . "Types";
        $this->settingTypesSrc = $settingTypesSrc ?? $backupTypeSrc;
    }

    /**
     * Build settings
     * @param bool $onlyTypes Default false, if true it will skip building definitions
     */
    public function build(bool $onlyTypes = false) {
        $this->buildSettingTypes();
        if ($onlyTypes) { return; }
        $this->buildSettingDefinitions();
    }

    /**
     * Setting Definitions in Settings/
     */
    private function buildSettingDefinitions() {
        $settingsMngr = (new Settings)->inst();

        $callback = function (string $class, string $path) use ($settingsMngr) {
            if (!is_subclass_of($class, AbstractSetting::class)) { return; } // For IDE
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

    /**
     * Setting Types in SettingTypes/
     *
     * ; Memory/time *could* be saved here if instead of being instantiated, a lazy load would be put in place instead
     */
    private function buildSettingTypes() {
        $settingsMngr = (new Settings)->inst();

        $callback = function (string $class, string $path) use ($settingsMngr) {
            if (!is_subclass_of($class, AbstractSettingType::class)) { return; } // For IDE

            $settingType = new $class;
            $name = $settingType->name;

            if (!isset($name)) {
                return;
            }

            $settingsMngr->addSettingType($name, $settingType);
        };

        $filter = function (string $className, string $path): bool {
            return is_subclass_of($className, AbstractSettingType::class);
        };

        $this->directoryIterator(true, $callback, $filter);
    }

}