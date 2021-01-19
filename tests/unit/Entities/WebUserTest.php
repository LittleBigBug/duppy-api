<?php declare(strict_types=1);
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\unit\Entities;

use Duppy\Abstracts\AbstractService;
use Duppy\DuppyServices\TokenManager;
use Duppy\Entities\Setting;
use Duppy\Entities\UserGroup;
use Duppy\Entities\WebUser;
use Duppy\Tests\Tools\DuppyTestCase;

final class WebUserTest extends DuppyTestCase {

    public function testWeightCheck() {
        $admin = new UserGroup;
        $moderator = new UserGroup;
        $donator = new UserGroup;
        $user = new UserGroup;

        $admin->setWeight(100);
        $admin->setName("Admin");

        $moderator->setWeight(50);
        $moderator->setName("Mods");

        $donator->setWeight(40);
        $donator->setName("Donators");

        $user->setWeight(10);
        $user->setName("Users");

        $user1 = new WebUser;
        $user2 = new WebUser;
        $user3 = new WebUser;
        $user4 = new WebUser;
        $user5 = new WebUser;

        $user1->setUsername("User1");
        $user1->addGroup($user);

        $user2->setUsername("User2");
        $user2->addGroup($user);
        $user2->addGroup($donator);

        $user3->setUsername("User3");
        $user3->addGroup($donator);
        $user3->addGroup($moderator);

        $user5->setUsername("User5");
        $user5->addGroup($moderator);

        $user4->setUsername("User4");
        $user4->addGroup($admin);

        // Check if the group inheritance works
        $this->assertSameA($user2->getWeight(), $donator->get("weight"));
        $this->assertSameA($user3->getWeight(), $moderator->get("weight"));

        $passesSetting = new Setting;
        $notPassesSetting = new Setting;

        $passesSetting->setSettingKey("equalWeightPasses");
        $passesSetting->setValue(true);

        $notPassesSetting->setSettingKey("equalWeightPasses");
        $notPassesSetting->setValue(false);

        $mockDbPasses = $this->doctrineMock(Setting::class, [ $passesSetting ]);
        $mockDbNotPasses = $this->doctrineMock(Setting::class, [ $notPassesSetting ]);

        // Mock equalWeightPasses true
        $this->databaseTest($mockDbPasses);

        // Weight Checks
        // Extra mod user, might as well run checks (all except admin)
        $this->assertSameA(false, $user5->weightCheck($user4));
        // Same weight should pass with setting
        $this->assertSameA(true, $user5->weightCheck($user3));
        $this->assertSameA(true, $user5->weightCheck($user2));
        $this->assertSameA(true, $user5->weightCheck($user1));

        // Admin checks true on all
        $this->assertSameA(true, $user4->weightCheck($user5));
        $this->assertSameA(true, $user4->weightCheck($user3));
        $this->assertSameA(true, $user4->weightCheck($user2));
        $this->assertSameA(true, $user4->weightCheck($user1));

        // Moderator checks (all but admin)
        $this->assertSameA(true, $user3->weightCheck($user1));
        $this->assertSameA(true, $user3->weightCheck($user2));
        $this->assertSameA(false, $user3->weightCheck($user4));
        // Same weight should pass with setting
        $this->assertSameA(true, $user3->weightCheck($user5));

        // Donator Check (only users & fellow donators)
        $this->assertSameA(true, $user2->weightCheck($user1));
        $this->assertSameA(false, $user2->weightCheck($user3));
        $this->assertSameA(false, $user2->weightCheck($user4));
        $this->assertSameA(false, $user2->weightCheck($user5));

        // User Check (only fellow users)
        $this->assertSameA(false, $user1->weightCheck($user2));
        $this->assertSameA(false, $user1->weightCheck($user3));
        $this->assertSameA(false, $user1->weightCheck($user4));
        $this->assertSameA(false, $user1->weightCheck($user5));

        // Mock equalWeightPasses false
        $this->databaseTest($mockDbNotPasses);

        // Redo Weight Checks with new settings
        // Extra mod user, might as well run checks (all except admin & mods)
        $this->assertSameA(false, $user5->weightCheck($user4));
        // Same weight should fail with setting
        $this->assertSameA(false, $user5->weightCheck($user3));
        $this->assertSameA(true, $user5->weightCheck($user2));
        $this->assertSameA(true, $user5->weightCheck($user1));

        // Admin checks true on all (except fellow admin with this setting)
        $this->assertSameA(true, $user4->weightCheck($user5));
        $this->assertSameA(true, $user4->weightCheck($user3));
        $this->assertSameA(true, $user4->weightCheck($user2));
        $this->assertSameA(true, $user4->weightCheck($user1));

        // Moderator checks (all but admin & fellow mods)
        $this->assertSameA(true, $user3->weightCheck($user1));
        $this->assertSameA(true, $user3->weightCheck($user2));
        $this->assertSameA(false, $user3->weightCheck($user4));
        // Same weight should fail with setting
        $this->assertSameA(false, $user3->weightCheck($user5));

        // Donator Check (only users)
        $this->assertSameA(true, $user2->weightCheck($user1));
        $this->assertSameA(false, $user2->weightCheck($user3));
        $this->assertSameA(false, $user2->weightCheck($user4));
        $this->assertSameA(false, $user2->weightCheck($user5));

        // User Check (nobody)
        $this->assertSameA(false, $user1->weightCheck($user2));
        $this->assertSameA(false, $user1->weightCheck($user3));
        $this->assertSameA(false, $user1->weightCheck($user4));
        $this->assertSameA(false, $user1->weightCheck($user5));
    }

    public function testIsMe() {
        // Mock Token Manager with info we need
        $mockToken1 = new AbstractService;
        $mockToken1->addFunction("getAuthToken", function () {
            return [
                "id" => 3,
            ];
        });

        $mockToken2 = new AbstractService;
        $mockToken2->addFunction("getAuthToken", function () {
            return [
                "id" => "4",
            ];
        });

        $user1 = new WebUser;
        $user1->setUsername("Bob");
        $user1->setId(3);

        $user2 = new WebUser;
        $user2->setUsername("Jon");
        $user2->setId(4);

        // Implement Mock TokenManager
        AbstractService::MockService(TokenManager::class, $mockToken1);

        $this->assertSameA(true, $user1->isMe());
        $this->assertSameA(false, $user2->isMe());

        AbstractService::MockService(TokenManager::class, $mockToken1);

        $this->assertSameA(true, $user2->isMe());
        $this->assertSameA(false, $user1->isMe());
    }

}