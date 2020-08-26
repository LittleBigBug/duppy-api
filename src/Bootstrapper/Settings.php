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
        try {
            $dirIterator = new \RecursiveDirectoryIterator(Util::combinePaths([DUPPY_PATH, "src", $this->settingsSrc], true));
            $iterator = new \RecursiveIteratorIterator($dirIterator);

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                // Get pathname
                $path = $file->getRealPath() ?: $file->getPathname();
                $path = str_replace(".php", "", $path);

                $classPath = substr(Util::toProjectPath($path), strlen("src/"));
                $class = "Duppy\\" . str_replace("/", "\\", $classPath);

                $key = $class::$key;

                if (!isset($key)) {
                    continue;
                }

                $settings[$key] = $class;
            }
        } catch (\UnexpectedValueException $ex) { }
    }

    public static function getSetting(string $key, $default = "") {
        $manager = Bootstrapper::getManager();
        $setting = $manager->getRepository("Duppy\Entities\Setting")->findOneBy([ "settingKey" => $key, ]);

        if (array_key_exists($key, static::$settings)) {
            $settingDef = static::$settings[$key];

            if (isset($settingDef)) {
                $default = $settingDef::$defaultValue;
            }
        }

        return $setting ?? $default;
    }

}
