<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Bootstrapper;

use Duppy\Abstracts\AbstractSetting;
use Duppy\DuppyServices\Settings;
use Duppy\Util;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

final class SettingsBuilder {

    /**
     * Path of settings to build
     *
     * @var string
     */
    public string $settingsSrc = "";

    /**
     * Settings builder constructor.
     *
     * @param string $settingsSrc
     */
    public function __construct(string $settingsSrc = "Settings") {
        $this->settingsSrc = $settingsSrc;
    }

    /**
     * Build settings
     */
    public function build() {
        try {
            $dirIterator = new RecursiveDirectoryIterator(Util::combinePaths([DUPPY_PATH, "src", $this->settingsSrc], true));
            $iterator = new RecursiveIteratorIterator($dirIterator);

            $settingsMngr = (new Settings)->inst();

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                // Get pathname
                $path = $file->getRealPath() ?: $file->getPathname();
                $path = str_replace(".php", "", $path);

                $classPath = substr(Util::toProjectPath($path), strlen("src/"));
                $class = "Duppy\\" . str_replace("/", "\\", $classPath);

                if (!is_subclass_of($class, AbstractSetting::class)) {
                    continue;
                }

                $key = $class::$key;

                if (!isset($key)) {
                    continue;
                }

                if ($class::$appSetting) {
                    $settingsMngr->addAppSetting($key);
                }

                $settingsMngr->addSetting($key, $class);
            }
        } catch (UnexpectedValueException) { }
    }

}