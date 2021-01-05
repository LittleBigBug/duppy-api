<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\unit\Services;

use Duppy\Abstracts\AbstractService;
use Duppy\DuppyServices\TokenManager;
use Duppy\DuppyServices\UserService;
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

        $this->assertSame("timmy.roblox2006", $newUser1->get("username"));
        $this->assertSame("timmy@roblox2006", $newUser2->get("username"));
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

        $this->assertSame(6, strlen((string) $setTo));
        $this->assertSame(6, strlen((string) $new));

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
        $user1->setEmail("other.private.email@jon.org");
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

        $this->assertSame($user1Private, $userService->getBasicInfo($user1));
        $this->assertSame($user2Details, $userService->getBasicInfo($user2));

        AbstractService::MockService(TokenManager::class, $mockToken2);

        $this->assertSame($user1Details, $userService->getBasicInfo($user1));
        $this->assertSame($user2Private, $userService->getBasicInfo($user2));

        AbstractService::MockService(TokenManager::class, $mockToken3);

        $this->assertSame($user1Details, $userService->getBasicInfo($user1));
        $this->assertSame($user2Details, $userService->getBasicInfo($user2));

        AbstractService::MockService(TokenManager::class, $mockToken4);

        $this->assertSame($user1Details, $userService->getBasicInfo($user1));
        $this->assertSame($user2Details, $userService->getBasicInfo($user2));
    }

}