<?php

namespace Duppy\Bootstrapper;

use Duppy\Util;

final class Settings {

    /**
     * Array of classes extending AbstractSetting
     *
     * @var array
     */
    public static array $settings = [];

    /**
     * Array of setting keys that are public app settings
     *
     * @var array
     */
    private static array $appSettings = [];

    /**
     * Array of categories and sub-categories
     *
     * @var array
     */
    public static array $categories = [];

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

                if (!is_subclass_of($class, "Duppy\Abstracts\AbstractSetting")) {
                    continue;
                }

                $key = $class::$key;

                if (!isset($key)) {
                    continue;
                }

                if ($class::$appSetting) {
                    static::$appSettings[] = $key;
                }

                static::$settings[$key] = $class;
                static::buildSettingsCategories();
            }
        } catch (\UnexpectedValueException $ex) { }
    }

    /**
     * Build settings nested categories and return it
     *
     * @return array
     */
    public static function buildSettingsCategories() {
        static::$categories = [];

        foreach (static::$settings as $key => $class) {
            $res = explode(".", $class::$category);
            $tab = &static::$categories;

            foreach ($res as $category) {
                if (!array_key_exists($category, $tab)) {
                    $tab[$category] = [];
                }

                $tab = &$tab[$category];
            }
        }

        return static::$categories;
    }

    /**
     * Returns all public app settings
     *
     * @return array
     */
    public static function getAppSettings() {
        return static::getSettings(static::$appSettings);
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
    public static function getSettings(array $keys, array $defaults = []) {
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
