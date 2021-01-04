<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\Tools\Tests\unit;

use DI\Container;
use Duppy\Tests\Tools\Bootstrapper\Bootstrapper;
use Duppy\Tests\Tools\DuppyTestCase;
use Duppy\Tests\Tools\Entities\Setting;
use Duppy\Tests\Tools\Entities\UserGroup;
use Duppy\Tests\Tools\Entities\WebUser;
use PHPUnit\Framework\TestCase;

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

}