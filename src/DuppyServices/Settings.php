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
use Duppy\Abstracts\AbstractSettingType;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyException;
use Duppy\Entities\Setting;
use Duppy\Enum\DuppyError;
use Duppy\Util;
use JetBrains\PhpStorm\Pure;

/**
 * Settings Service
 *
 * Class Settings
 * @package Duppy\DuppyServices
 */
final class Settings extends AbstractService {

    /**
     * Array of classes extending AbstractSetting
     *
     * @var array
     */
    public array $settings = [];

    /**
     * Array of classes extending AbstractSettingType
     *
     * @var array
     */
    public array $settingTypes = [];

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
     * Build settings nested categories and return it
     * Returns an array of all settings' categories. The categories are separated by dots and are nested accordingly
     *
     * Todo; docs for this
     *
     * This scenario, two items:
     *
     * Item1 category of system.some.category
     * Item2 category of system.other.place
     * will return:
     *
     * $return = [
     *   "system" => [
     *     "some" => [
     *       "category" => [], // This array would be empty, but with $addSettings = true the setting keys are added here.
     *     ],
     *     "other" = [
     *       "place" = [] // same as above
     *     ],
     *   ],
     * ];
     *
     * @param bool $addSettings
     * @return array
     */
    public function getSettingsCategories(bool $addSettings = false): array {
        $this->categories = [];

        foreach ($this->settings as $key => $class) {
            $category = $this->extractValueFromSetting($class, "category");
            $res = explode(".", $category);
            $endK = array_key_last($res);

            // tab is always a reference to directly modify recursively
            $tab = &$this->categories;

            // Loop the separated category string
            foreach ($res as $cKey => $category) {
                // Verify the nested table exists
                if (!array_key_exists($category, $tab)) {
                    $tab[$category] = [];
                }

                // Go down into it (set tab to newly created)
                $tab = &$tab[$category];

                // On the last value and if specified, add the key of the setting to it
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
     * This means clients are able to access the setting key & value at any time
     *
     * @param string $key
     */
    public function addAppSetting(string $key) {
        $this->appSettings[] = $key;
    }

    /**
     * @param string $key
     * @param string $setting AbstractSetting Class
     */
    public function addSetting(string $key, string $setting) {
        $this->settings[$key] = $setting;
    }

    /**
     * @param string $name
     * @param AbstractSettingType $settingType
     */
    public function addSettingType(string $name, AbstractSettingType $settingType) {
        $this->settingTypes[$name] = $settingType;
    }

    /**
     * Returns all public app settings
     *
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function getAppSettings(): array {
        return $this->getSettings($this->appSettings);
    }

    /**
     * Gets a setting's definition
     *
     * @param string $key
     * @return string|array string of AbstractSetting definition or manually created setting array
     * @throws DuppyException ErrType noneFound if the key is nonexistent
     */
    #[Pure]
    public function getSettingDefinition(string $key): string|array {
        if (!array_key_exists($key, $this->settings)) {
            throw new DuppyException(DuppyError::noneFound(), "Setting definition missing");
        }

        return $this->settings[$key];
    }

    /**
     * Same as getSettingDefinition but instead of throwing an exception it returns null
     *
     * @param string $key
     * @return AbstractSetting|null
     */
    #[Pure]
    public function getSettingDefinitionNull(string $key): ?AbstractSetting {
        return Util::indArrayNull($this->settings, $key);
    }

    /**
     * Return an array of settings by multiple keys and checks for defaults
     *
     * @param array $keys
     * @param array $defaults
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException ErrType nonefound if a setting's type is missing
     */
    public function getSettings(array $keys, array $defaults = []): array {
        $manager = Bootstrapper::getContainer()->get("database");
        $settings = $manager->getRepository(Setting::class)->findBy(["settingKey" => $keys,]);

        $ret = [];

        // Process setting value types
        foreach ($settings as $setting) {
            $key = $setting->get("settingKey");
            $value = $setting->get("value");

            try {
                $ret[$key] = $this->processSettingValue($key, $value);
            } catch (DuppyException) { } // This shouldn't happen
        }

        // Check for defaults
        foreach ($keys as $key) {
            $exists = array_key_exists($key, $ret);

            // If the value is set skip it
            if ($exists) {
                continue;
            }

            $setDef = "";

            // Argument default overrides
            if (array_key_exists($key, $defaults) && !empty($defaults[$key])) {
                $setDef = $defaults[$key];
            }
            // Defaults in setting definition
            elseif (array_key_exists($key, $this->settings)) {
                $settingDef = $this->settings[$key];
                $setDef = $this->extractValueFromSetting($settingDef, "defaultValue");
            }

            // Assure the default is the right type
            $typeClass = $this->getSettingType($key);

            if (!$typeClass->checkIsOfType($setDef)) {
                $setDef = $typeClass->parse($setDef);
            }

            $ret[$key] = $setDef;
        }

        return $ret;
    }

    /**
     * Return a single setting by key
     *
     * @param string $key
     * @param string $default
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException ErrType nonefound if a setting's type is missing
     */
    public function getSetting(string $key, $default = ""): mixed {
        $result = $this->getSettings([ $key, ], [ $key => $default, ]);
        return $result[$key];
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

            if (array_key_exists($ea, $this->settingTypes)) {
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
     * Gets the setting type from its definition
     *
     * @param string $key
     * @return AbstractSettingType
     * @throws DuppyException ErrType noneFound if the setting type is missing
     */
    public function getSettingType(string $key): AbstractSettingType {
        $settingDef = $this->getSettingDefinition($key);

        // Process required string settings
        $required = $this->extractValueFromSetting($settingDef, "required");
        $reqSettings = $this->processSettingRequirements($required);

        $type = array_key_exists("type", $reqSettings) ? $reqSettings["type"] : "string";

        if (!array_key_exists($type, $this->settingTypes)) {
            throw new DuppyException(DuppyError::noneFound(), "Setting type missing");
        }

        return $this->settingTypes[$type];
    }

    /**
     * Gets the setting value by the definition type
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     * @throws DuppyException ErrType noneFound if the setting key is nonexistent
     */
    public function processSettingValue(string $key, mixed $value): mixed {
        $typeClass = $this->getSettingType($key);
        return $typeClass->parse($value);
    }

    /**
     * Creates a setting dynamically
     *
     * @param string $key
     * @param array $settingValues
     * @throws DuppyException ErrType alreadyExists if the settingKey is created already
     */
    public function createSetting(string $key, array $settingValues) {
        if (array_key_exists($key, $this->settings)) {
            throw new DuppyException(DuppyError::alreadyExists());
        }

        $settingValues["dynamic"] = true;
        $settingValues["key"] = $key;

        $this->settings[$key] = $settingValues;
    }

    /**
     * Sets a setting's value based on the definition type
     *
     * @param string $key
     * @param mixed $value
     * @param bool $persistNow
     * @param bool $flushNow If true it will only work if persistNow is also true
     * @return Setting
     * @throws DependencyException
     * @throws DuppyException ErrType notFound if setting def/type is missing or incorrectType if the value isnt compatible
     * @throws NotFoundException
     */
    public function changeSetting(string $key, mixed $value, bool $persistNow = false, bool $flushNow = false): Setting {
        $getType = $this->getSettingType($key);
        $storeVal = $getType->store($value);

        $dbo = Bootstrapper::getContainer()->get("database");
        $setting = $dbo->getRepository(Setting::class)->findOneBy(["settingKey" => $key,]);

        if ($setting == null) {
            $setting = new Setting;
            $setting->setSettingKey($key);
        }

        $setting->setValue($storeVal);

        if ($persistNow) {
            $dbo->persist($setting);

            if ($flushNow) {
                $dbo->flush();
            }
        }

        return $setting;
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
        $isArray = is_array($setting);

        if (!is_subclass_of($setting, AbstractSetting::class) || $isArray) {
            if ($isArray && array_key_exists($settingKey, $setting)) {
                return $setting[$settingKey];
            }

            return null;
        }

        return $setting::$$settingKey;
    }

}
