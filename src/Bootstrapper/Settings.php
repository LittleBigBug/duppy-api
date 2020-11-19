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

    /**
     * Return a single setting by key
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function getSetting(string $key, $default = "") {
        $result = static::getSettings([ $key, ], [ $key => $default, ]);
        return $result[$key];
    }

    /**
     * Return an array of settings by multiple keys and checks for defaults
     *
     * @param array $keys
     * @param array $defaults
     * @return array
     */
    public static function getSettings(array $keys, array $defaults) {
        $manager = Bootstrapper::getManager();
        $settings = $manager->getRepository("Duppy\Entities\Setting")->findBy([ "settingKey" => $keys, ]);

        $ret = [];

        foreach ($settings as $setting) {
            $ret[$setting->settingKey] = $setting->value;
        }

        foreach ($keys as $key) {
            $exists = array_key_exists($key, $ret);
            $useDefault = false;

            if (!$exists || ($exists && empty($ret[$key]))) {
                $useDefault = true;
            }

            if (!$useDefault) {
                continue;
            }

            if (array_key_exists($key, $defaults) && !empty($defaults[$key])) {
                $ret[$key] = $defaults[$key];
                continue;
            }

            if (array_key_exists($key, static::$settings)) {
                $settingDef = static::$settings[$key];
                $ret[$key] = $settingDef::$defaultValue;
                continue;
            }

            $ret[$key] = "";
        }

        return $ret;
    }

}
