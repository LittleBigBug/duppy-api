<?php declare(strict_types=1);
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\unit\Entities;

use Duppy\Entities\UserGroup;
use Duppy\Tests\Tools\DuppyTestCase;

final class UserGroupTest extends DuppyTestCase {

    public function testParents() {
        $users = new UserGroup;
        $users->setName("users");

        $members = new UserGroup;
        $members->setName("members");
        $members->setParent($users);

        $donators = new UserGroup;
        $donators->setName("donators");
        $donators->setParent($members);

        $mods = new UserGroup;
        $mods->setName("moderators");
        $mods->setParent($members);

        $admins = new UserGroup;
        $admins->setName("admins");
        $admins->setParent($mods);

        $userParents = [];
        $membersParents = [$users];
        $donatorsModsParents = [$members, $users];
        $adminsParents = [$mods, $members, $users];

        $this->assertSameA($userParents, $users->getParents());
        $this->assertSameA($membersParents, $members->getParents());
        $this->assertSameA($donatorsModsParents, $donators->getParents());
        $this->assertSameA($donatorsModsParents, $mods->getParents());
        $this->assertSameA($adminsParents, $admins->getParents());
    }

}