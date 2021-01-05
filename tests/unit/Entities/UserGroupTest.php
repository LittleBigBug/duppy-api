<?php declare(strict_types=1);
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\Tools\Tests\unit;

use Duppy\Entities\UserGroup;
use PHPUnit\Framework\TestCase;

final class UserGroupTest extends TestCase {

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

        $this->assertSame($userParents, $users->getParents());
        $this->assertSame($membersParents, $members->getParents());
        $this->assertSame($donatorsModsParents, $donators->getParents());
        $this->assertSame($donatorsModsParents, $mods->getParents());
        $this->assertSame($adminsParents, $admins->getParents());
    }

}