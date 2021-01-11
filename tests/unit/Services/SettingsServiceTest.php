<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\unit\Services;

use Duppy\Builders\SettingsBuilder;
use Duppy\DuppyServices\Settings;
use Duppy\Entities\Setting;
use Duppy\Tests\Tools\DuppyTestCase;

class SettingsServiceTest extends DuppyTestCase {

    public function testGetSettingsCategories() {
        $manager = (new Settings)->inst();

        $manager->createSetting("item1", [
            "category" => "category.test.one",
        ]);

        $manager->createSetting("item2", [
            "category" => "category.test.two",
        ]);

        $manager->createSetting("item3", [
            "category" => "category.anotherTest.three",
        ]);

        $manager->createSetting("item4", [
            "category" => "test",
        ]);

        $manager->createSetting("item5", [
            "category" => "test.testAgain",
        ]);

        $manager->createSetting("item6", [
            "category" => "test.testAgain",
        ]);

        $manager->createSetting("item7", [
            "category" => "test.test.test.test.test",
        ]);

        // Expected tables
        $should = [
            "category" => [
                "test" => [
                    "one" => [],
                    "two" => [],
                ],
                "anotherTest" => [
                    "three" => [],
                ]
            ],
            "test" => [
                "testAgain" => [],
                "test" => [
                    "test" => [
                        "test" => [
                            "test" => [],
                        ],
                    ],
                ],
            ],
        ];
        $shouldWithSettings = [
            "category" => [
                "test" => [
                    "one" => [ "item1", ],
                    "two" => [ "item2", ],
                ],
                "anotherTest" => [
                    "three" => [ "item3", ],
                ]
            ],
            "test" => [
                "item4",

                "testAgain" => [
                    "item5",
                    "item6",
                ],
                "test" => [
                    "test" => [
                        "test" => [
                            "test" => [ "item7", ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSameA($should, $manager->getSettingsCategories(false));
        $this->assertSameA($shouldWithSettings, $manager->getSettingsCategories(true));
    }

    // This tests types, too
    public function testGetSettings() {
        (new SettingsBuilder)->build(true);
        $manager = (new Settings)->inst();

        $manager->createSetting("item1", [ "required" => "string", ]);
        $manager->createSetting("item2", [ "required" => "float", ]);

        $item3 = [
            "required" => "boolean",
            "defaultValue" => false,
        ];
        $item4 = [
            "required" => "integer",
            "defaultValue" => 420,
        ];

        $manager->createSetting("item3", $item3);
        $manager->createSetting("item3pt2", $item3);

        $manager->createSetting("item4", $item4);
        $manager->createSetting("item4pt2", $item4);
        $manager->createSetting("item4pt3", $item4);
        $manager->createSetting("item4pt4", $item4);

        $item5 = [ "required" => "json", "defaultValue" => [ "a" => "b" ] ];
        // Json type is basically just json_encode & json_decode wrapper, any JSONSerializable works
        $manager->createSetting("item5", $item5);

        $setting1 = new Setting;
        $setting2 = new Setting;

        $setting3 = new Setting; // Test definition default
        $setting3pt2 = new Setting; // Test value

        $setting4 = new Setting; // Test definition default
        $setting4pt2 = new Setting; // Test function default
        $setting4pt3 = new Setting; // Test value
        $setting4pt4 = new Setting; // Test value and function default

        $setting5 = new Setting;
        $setting5pt2 = new Setting;

        $setting1->setSettingKey("item1");
        $setting1->setValue("Test");

        $setting2->setSettingKey("item2");
        $setting2->setValue(12.3);

        $setting3->setSettingKey("item3");

        $setting3pt2->setSettingKey("item3");
        $setting3pt2->setValue(true);

        $setting4->setSettingKey("item4");

        $setting4pt2->setSettingKey("item4pt2");

        $setting4pt3->setSettingKey("item4pt3");
        $setting4pt3->setValue(69);

        $setting4pt4->setSettingKey("item4pt4");
        $setting4pt4->setValue(69);

        $testArray = [
            "test" => "a",
            "test2" => 123,
            "test3" => [ "a", "b", "c", ]
        ];

        $setting5->setSettingKey("item5");

        $setting5pt2->setSettingKey("item5pt2");
        $setting5pt2->setValue($testArray);

        $settingsList = [
            $setting1, $setting2, // String & float
            $setting3, $setting3pt2, // Basic Default + boolean
            $setting4, $setting4pt2, $setting4pt3, $setting4pt4, // Default tests + integer
            $setting5, $setting5pt2, // JSON + default
        ];

        $mockDb = $this->doctrineMock(Setting::class, $settingsList);
        $this->databaseTest($mockDb);

        $settings = $manager->getSettings([ "item1", "item2", "item3", "item4", "item5", ],
                                          [ "item4pt2" => 2496, "item4pt4" => 2496, ]); // Defaults from function

        $this->assertSameA("Test", $settings["item1"]);
        $this->assertSameA(12.3, $settings["item2"]);

        $this->assertSameA(false, $settings["item3"]); // Should fallback to definition default
        $this->assertSameA(true, $settings["item3pt2"]); // Actual set value

        $this->assertSameA(420, $settings["item4"]); // Should fallback to definition default
        $this->assertSameA(2496, $settings["item4pt2"]); // Should fallback to get function default
        $this->assertSameA(69, $settings["item4pt3"]); // Actual set value
        $this->assertSameA(69, $settings["item4pt4"]); // Actual set value (testing with function default set)

        $this->assertSameA([ "a" => "b", ], $settings["item5"]); // Default test
        $this->assertSameA($testArray, $settings["item5pt2"]); // Actual set value
    }

}