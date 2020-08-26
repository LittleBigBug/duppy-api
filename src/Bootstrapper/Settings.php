<?php

namespace Duppy\Bootstrapper;

use Duppy\Util;

final class Settings {

    /**
     * Map of settings
     *
     * @var array
     */
    public static array $settings = [];

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
    public function build(): void {
        $dirIterator = new \DirectoryIterator(Util::combinePaths([DUPPY_PATH, "src", $this->settingsSrc], true));
        $iterator = new \RecursiveIteratorIterator($dirIterator);

        foreach ($iterator as $file) {
            // Check if file is a directory
            if (!$file->isDir() || $file->isDot()) {
                continue;
            }

            // Get pathname
            $path = $file->getRealPath() ?: $file->getPathname();

            $classPath = substr(Util::toProjectPath($path), strlen("src/"));
            $class = "Duppy\\" . str_replace("/", "\\", $classPath);

            $key = $class::$key;

            if (!isset($key)) {
                continue;
            }

           $settings[$key] = $class;
        }
    }

    public static function getSetting(string $key): object {
        $manager = Bootstrapper::getManager();
        $setting = $manager->getRepository("Duppy\Entities\Setting")->findOneBy([ "settingKey" => $key, ]);

        $settingDef = static::$settings[$key];
        $default = null;

        if (isset($settingDef)) {
            $default = $settingDef::$defaultValue;
        }

        return $setting ?? $default;
    }

}
