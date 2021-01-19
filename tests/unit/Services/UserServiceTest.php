<?php declare(strict_types=1);
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\unit\Services;

use Duppy\Abstracts\AbstractService;
use Duppy\DuppyServices\TokenManager;
use Duppy\DuppyServices\UserService;
use Duppy\Entities\Setting;
use Duppy\Entities\UserGroup;
use Duppy\Entities\WebUser;
use Duppy\Tests\Tools\DuppyTestCase;

/**
 * Class UserServiceTest
 *
 * A lot of UserService functions are not tested because they depend on the ORM code ONLY
 *
 * @package Duppy\Tests\unit\Services
 */
class UserServiceTest extends DuppyTestCase {

    public function testCreateUser() {
        $userService = (new UserService)->inst();

        $newUser1 = $userService->createUser("timmy.roblox2006@yahoo.com", "test", false);
        $newUser2 = $userService->createUser("timmy@roblox2006@yahoo.com", "test", false);

        $this->assertSameA("timmy.roblox2006", $newUser1->get("username"));
        $this->assertSameA("timmy@roblox2006", $newUser2->get("username"));
    }

    public function testGenUniqueTempCode() {
        $userService = (new UserService)->inst();

        $setTo = 0;
        $new = 0;
        $changed = false;

        $userService->generateUniqueTempCode(function (int $newInt) use (&$setTo, &$new, &$changed) {
            if ($changed) {
                $new = $newInt;
                return true;
            }

            $setTo = $newInt;
            $changed = true;
            return false;
        });

        $this->assertSameA(6, strlen((string) $setTo));
        $this->assertSameA(6, strlen((string) $new));

        $this->assertNotSame($new, $setTo);

        // Test infinite loop
        $res = $userService->generateUniqueTempCode(function (int $newInt) use ($setTo, $new, $changed) {
            return false;
        });

        $this->assertNull($res);
    }

    public function testGetBasicInfo() {
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

        $mockToken3 = new AbstractService;
        $mockToken3->addFunction("getAuthToken", function () { return []; });

        $mockToken4 = new AbstractService;
        $mockToken4->addFunction("getAuthToken", function () { return null; });

        $user1 = new WebUser;
        $user1->setUsername("Bob");
        $user1->setEmail("some.private.email@bob.com");
        $user1->setId(3);

        $user2 = new WebUser;
        $user2->setUsername("Jon");
        $user2->setEmail("other.private.email@jon.org");
        $user2->setId(4);

        $userService = (new UserService)->inst();

        $user1Details = [
            "id" => 3,
            "username" => "Bob",
            "avatarUrl" => "",
        ];
        $user2Details = [
            "id" => 4,
            "username" => "Jon",
            "avatarUrl" => "",
        ];

        $user1Private = array_merge($user1Details, [ "email" => "some.private.email@bob.com", ]);
        $user2Private = array_merge($user2Details, [ "email" => "other.private.email@jon.org", ]);

        // Implement Mock TokenManager
        AbstractService::MockService(TokenManager::class, $mockToken1);

        $this->assertSameA($user1Private, $userService->getBasicInfo($user1));
        $this->assertSameA($user2Details, $userService->getBasicInfo($user2));

        AbstractService::MockService(TokenManager::class, $mockToken2);

        $this->assertSameA($user1Details, $userService->getBasicInfo($user1));
        $this->assertSameA($user2Private, $userService->getBasicInfo($user2));

        AbstractService::MockService(TokenManager::class, $mockToken3);

        $this->assertSameA($user1Details, $userService->getBasicInfo($user1));
        $this->assertSameA($user2Details, $userService->getBasicInfo($user2));

        AbstractService::MockService(TokenManager::class, $mockToken4);

        $this->assertSameA($user1Details, $userService->getBasicInfo($user1));
        $this->assertSameA($user2Details, $userService->getBasicInfo($user2));
    }

    /**
     * Tests for
     *  loggedInUserAgainstUserPerm
     *  givePermission
     * and some permission ordering, usergroup permission assignments (weighted) along with personal permission assignments
     */
    public function testUserServicePermissions() {
        // Creating a new local instance (mock later)
        $userSrv = new UserService;

        $regPerm = "self.perm";
        $overridePerm = "some.override.perm";
        $donoPerm = "donator.perm";

        $admin = new UserGroup;
        $moderator = new UserGroup;
        $donator = new UserGroup;
        $user = new UserGroup;

        $admin->setWeight(100);
        $admin->setParent($moderator);
        $admin->setName("Admin");

        $userSrv->givePermission($admin, "*", persistNow: false);

        $moderator->setWeight(50);
        $moderator->setParent($user);
        $moderator->setName("Mods");

        $userSrv->givePermission($moderator, $overridePerm, persistNow: false);

        $donator->setWeight(40);
        $donator->setParent($user);
        $donator->setName("Donators");

        $userSrv->givePermission($donator, $donoPerm, persistNow: false);
        // A moderator who is also a donator should still have access to some.override.function because of higher weight.
        $userSrv->givePermission($donator, "-" . $overridePerm, persistNow: false);
        // A moderator as a donator should no longer inherit this permission from user (or any donator)
        $userSrv->givePermission($donator, "-" . $regPerm, persistNow: false);

        $user->setWeight(10);
        $user->setName("Users");

        // Regular permission if the action is against themselves
        $userSrv->givePermission($user, $regPerm, persistNow: false);

        $user1 = new WebUser;
        $user2 = new WebUser;
        $user3 = new WebUser;
        $user4 = new WebUser;
        $user5 = new WebUser;
        $user6 = new WebUser;

        $user1->setUsername("User1");
        $user1->setId(1);
        $user1->addGroup($user);

        $user2->setUsername("User2");
        $user2->setId(2);
        $user2->addGroup($user);
        $user2->addGroup($donator);

        $user3->setUsername("User3");
        $user3->setId(3);
        $user3->addGroup($donator);
        $user3->addGroup($moderator);

        $user4->setUsername("User4");
        $user4->setId(4);
        $user4->addGroup($admin);

        $user5->setUsername("User5");
        $user5->setId(5);
        $user5->addGroup($moderator);

        $user6->setUsername("User6");
        $user6->setId(6);
        $user6->addGroup($user);
        $user6->addGroup($donator);

        // This should override $donator negation
        $userSrv->givePermission($user6, $overridePerm, persistNow: false);

        $users = [
            1 => $user1, 2 => $user2,
            3 => $user3, 4 => $user4,
            5 => $user5, 6 => $user6,
        ];

        $loggedUser = 1;

        $userServiceMock = new AbstractService;
        $userServiceMock->addFunction("getUser", function (int $id) use ($users): WebUser {
            return $users[$id];
        });
        $userServiceMock->addFunction("getLoggedInUser", function () use ($users, &$loggedUser): ?WebUser {
            if ($loggedUser == null) {
                return null;
            }

            return $users[$loggedUser];
        });

        AbstractService::MockService(UserService::class, $userServiceMock);

        $setting = new Setting;

        $setting->setSettingKey("equalWeightPasses");
        $setting->setValue(false);

        $mockDb = $this->doctrineMock(Setting::class, [ $setting ]);

        $this->databaseTest($mockDb);

        $loggedUser = 1;

        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(1, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(2, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(3, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(4, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(5, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(6, $overridePerm, $regPerm));

        $loggedUser = 2;

        // Donators cant do this to themselves (for whatever reason)
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(1, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(2, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(3, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(4, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(5, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(6, $overridePerm, $regPerm));

        $loggedUser = 3;

        // Donators cant do this to themselves, but mods should be able to do unto others below their weight
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(1, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(2, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(3, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(4, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(5, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(6, $overridePerm, $regPerm));

        $loggedUser = 4;

        // Admin can do anything to others
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(1, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(2, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(3, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(4, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(5, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(6, $overridePerm, $regPerm));

        $loggedUser = 5;

        // Moderator again but not a donator so can do to themselves
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(1, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(2, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(3, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(4, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(5, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(6, $overridePerm, $regPerm));

        $loggedUser = 6;

        // User donator has a personally given permission with the override perm (others)
        // Can only do it to users (below weight)
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(1, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(2, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(3, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(4, $overridePerm, $regPerm));
        $this->assertSameA(false, $userSrv->loggedInUserAgainstUserPerm(5, $overridePerm, $regPerm));
        $this->assertSameA(true, $userSrv->loggedInUserAgainstUserPerm(6, $overridePerm, $regPerm));

        $loggedUser = null;

        // Some hasPermission checks while we are here

        $this->assertSameA(true, $user->hasPermission($regPerm)); // Groups
        $this->assertSameA(false, $user->hasPermission($overridePerm));
        $this->assertSameA(false, $user->hasPermission($donoPerm));

        $this->assertSameA(false, $donator->hasPermission($regPerm));
        $this->assertSameA(false, $donator->hasPermission($overridePerm));
        $this->assertSameA(true, $donator->hasPermission($donoPerm));

        $this->assertSameA(true, $moderator->hasPermission($regPerm));
        $this->assertSameA(true, $moderator->hasPermission($overridePerm));
        $this->assertSameA(false, $moderator->hasPermission($donoPerm));

        $this->assertSameA(true, $admin->hasPermission($regPerm));
        $this->assertSameA(true, $admin->hasPermission($overridePerm));
        $this->assertSameA(true, $admin->hasPermission($donoPerm));


        $this->assertSameA(true, $user1->hasPermission($regPerm)); // Users
        $this->assertSameA(false, $user1->hasPermission($overridePerm));
        $this->assertSameA(false, $user1->hasPermission($donoPerm));

        $this->assertSameA(false, $user2->hasPermission($regPerm));
        $this->assertSameA(false, $user2->hasPermission($overridePerm));
        $this->assertSameA(true, $user2->hasPermission($donoPerm));

        $this->assertSameA(false, $user3->hasPermission($regPerm));
        $this->assertSameA(true, $user3->hasPermission($overridePerm));
        $this->assertSameA(true, $user3->hasPermission($donoPerm));

        $this->assertSameA(true, $user4->hasPermission($regPerm));
        $this->assertSameA(true, $user4->hasPermission($overridePerm));
        $this->assertSameA(true, $user4->hasPermission($donoPerm));

        $this->assertSameA(true, $user5->hasPermission($regPerm));
        $this->assertSameA(true, $user5->hasPermission($overridePerm));
        $this->assertSameA(false, $user5->hasPermission($donoPerm));

        $this->assertSameA(false, $user6->hasPermission($regPerm));
        $this->assertSameA(true, $user6->hasPermission($overridePerm));
        $this->assertSameA(true, $user6->hasPermission($donoPerm));
    }

}