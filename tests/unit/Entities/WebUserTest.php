<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\Tools\Tests\unit;

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

        $user1->setUsername("User1");
        $user1->addGroup($user);

        $user2->setUsername("User2");
        $user2->addGroup($user);
        $user2->addGroup($donator);

        $user3->setUsername("User3");
        $user2->addGroup($donator);
        $user3->addGroup($moderator);

        $user3->setUsername("User4");
        $user4->addGroup($admin);

        // Check if the group inheritance works
        $this->assertSame($user2->getWeight(), $donator->get("weight"));
        $this->assertSame($user3->getWeight(), $moderator->get("weight"));

        $mockDb = $this->doctrineMock(Setting::class, [

        ]);
        $this->databaseTest($mockDb);

        // Weight Checks
        $this->assertSame(false, $user1->weightCheck($user2));
        $this->assertSame(false, $user2->weightCheck($user3));
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

        $this->assertSame(true, $user1->isMe());
        $this->assertSame(false, $user2->isMe());

        AbstractService::MockService(TokenManager::class, $mockToken1);

        $this->assertSame(true, $user2->isMe());
        $this->assertSame(false, $user1->isMe());
    }

}