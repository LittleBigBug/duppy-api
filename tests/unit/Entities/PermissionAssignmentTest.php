<?php declare(strict_types=1);
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\unit\Entities;

use Duppy\DuppyServices\EnvironmentService;
use Duppy\Entities\Environment;
use Duppy\Entities\PermissionAssignment;
use Duppy\Tests\Tools\DuppyTestCase;

final class PermissionAssignmentTest extends DuppyTestCase {

    public function testNegativeAssignment() {
        $positive = new PermissionAssignment;
        $negative = new PermissionAssignment;

        $positive->setPermission("some.permission.type");
        $negative->setPermission("-another.permission.type");

        $this->assertSameA(true, $positive->getPermissionEval());
        $this->assertSameA(false, $negative->getPermissionEval());
    }

    public function testEnvironmentAssignment() {
        $env = new Environment;
        $oEnv = new Environment;

        $env->setEnabled(true);
        $env->setName("test-environment");

        $oEnv->setEnabled(true);
        $oEnv->setName("other-environment");

        (new EnvironmentService)->inst()->setEnvironment($env);

        $noEnvPerm = new PermissionAssignment;
        $thisEnvPerm = new PermissionAssignment;
        $otherEnvPerm = new PermissionAssignment;

        $noEnvPerm->setPermission("test.permission");

        $thisEnvPerm->setPermission("other.permission");
        $thisEnvPerm->setEnvironment($env);

        $otherEnvPerm->setPermission("another.permission");
        $otherEnvPerm->setEnvironment($oEnv);

        $this->assertSameA(true, $noEnvPerm->inThisEnvironment());
        $this->assertSameA(true, $thisEnvPerm->inThisEnvironment());
        $this->assertSameA(false, $otherEnvPerm->inThisEnvironment());
    }

}