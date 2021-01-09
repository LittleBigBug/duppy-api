<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\DuppyServices;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractService;
use Duppy\Abstracts\AbstractSetting;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Entities\Setting;
use Duppy\Util;
use JetBrains\PhpStorm\Pure;

final class Settings extends AbstractService {

    /**
     * Array of classes extending AbstractSetting
     *
     * @var array
     */
    public array $settings = [];

    /**
     * Array of setting keys that are public app settings
     *
     * @var array
     */
    private array $appSettings = [];

    /**
     * Array of setting keys that are user settings (per-user preferences)
     *
     * @var array
     */
    private array $userSettings = [];

    /**
     * Array of categories and sub-categories
     *
     * @var array
     */
    public array $categories = [];

    /**
     * Array of allowed types for settings, with their respective 'casting' function
     *
     * @var array
     */
    private array $types = [
        "boolean" => 'boolval',
        "string" => '', // Should already be a string
        "integer" => 'intval',
        "float" => 'floatval',
        "array" => 'json_decode',
    ];

    /**
     * Build settings nested categories and return it
     *
     * @return array
     */
    public function getSettingsCategories(bool $addSettings = false): array {
        $this->categories = [];

        foreach ($this->settings as $key => $class) {
            $res = explode(".", $class::$category);
            $endK = array_key_last($res);
            $tab = &$this->categories;

            foreach ($res as $cKey => $category) {
                if (!array_key_exists($category, $tab)) {
                    $tab[$category] = [];
                }

                $tab = &$tab[$category];

                if ($endK == $cKey && $addSettings) {
                    $tab[] = $key;
                }
            }
        }

        return $this->categories;
    }

    /**
     * Convenience function for getSettingsCategories(true);
     *
     * @return array
     */
    public function getSettingsCategoriesWithSettings(): array {
        return $this->getSettingsCategories(true);
    }

    /**
     * Marks a setting key as a public app setting
     *
     * @param string $key
     */
    public function addAppSetting(string $key) {
        $this->appSettings[] = $key;
    }

    /**
     * @param string $key
     * @param AbstractSetting $setting
     */
    public function addSetting(string $key, AbstractSetting $setting) {
        $this->settings[$key] = $setting;
    }

    /**
     * Returns all public app settings
     *
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getAppSettings(): array {
        return $this->getSettings($this->appSettings);
    }

    /**
     * Return a single setting by key
     *
     * @param string $key
     * @param string $default
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSetting(string $key, $default = ""): string {
        $result = $this->getSettings([ $key, ], [ $key => $default, ]);
        return $result[$key];
    }

    /**
     * Return an array of settings by multiple keys and checks for defaults
     *
     * @param array $keys
     * @param array $defaults
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSettings(array $keys, array $defaults = []): array {
        $manager = Bootstrapper::getContainer()->get("database");
        $settings = $manager->getRepository(Setting::class)->findBy(["settingKey" => $keys,]);

        $ret = [];

        foreach ($settings as $setting) {
            $key = $setting->get("settingKey");
            $value = $setting->get("value");

            $settingDef = $this->settings[$key];
            $required = $this->extractValueFromSetting($settingDef, "required");
            $reqSettings = $this->processSettingRequirements($required);
            $type = array_key_exists("type", $reqSettings) ? $reqSettings["type"] : "string";
            $typeFunc = $this->types[$type];

            if (!empty($typeFunc)) {
                $ret[$key] = $typeFunc($setting->get("value"));
            } else {
                $ret[$key] = $value;
            }
        }

        foreach ($keys as $key) {
            $exists = array_key_exists($key, $ret);

            if ($exists) {
                continue;
            }

            if (array_key_exists($key, $defaults) && !empty($defaults[$key])) {
                $ret[$key] = $defaults[$key];
                continue;
            }

            if (array_key_exists($key, $this->settings)) {
                $settingDef = $this->settings[$key];
                $ret[$key] = $this->extractValueFromSetting($settingDef, "defaultValue");
                continue;
            }

            $ret[$key] = "";
        }

        return $ret;
    }

    /**
     * Process and return an array of data based on AbstractSetting::$required
     *
     * @param $req
     * @return array
     */
    public function processSettingRequirements($req): array {
        $stgs = [];
        $sep = explode("|", $req);

        foreach ($sep as $ea) {
            if ($ea === "notnull") {
                $stgs["notnull"] = true;
                continue;
            }

            if (array_key_exists($ea, $this->types)) {
                $stgs["type"] = $ea;
                continue;
            }

            if (str_starts_with($ea, "max:")) {
                $stgs["max"] = intval(substr($ea, 4));
                continue;
            }
        }

        return $stgs;
    }

    /**
     * Creates a setting dynamically
     *
     * @param string $key
     * @param array $settingValues
     */
    public function createSetting(string $key, array $settingValues) {
        if (array_key_exists($key, $this->settings)) {
            throw new \OverflowException("setting already exists by that key");
        }

        $settingValues["dynamic"] = true;

        $this->settings[$key] = $settingValues;
    }

    /**
     * Gets a value from a setting whether it is a static AbstractSetting or an array
     *
     * @param $setting
     * @param string $settingKey
     * @return mixed
     */
    #[Pure]
    public function extractValueFromSetting($setting, string $settingKey): mixed {
        if (!is_subclass_of($setting, AbstractSetting::class) || is_array($setting)) {
            if (array_key_exists($settingKey, $setting)) {
                return $setting[$settingKey];
            }

            return null;
        }

        return $setting::$$settingKey;
    }

}
