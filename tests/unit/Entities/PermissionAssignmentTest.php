<?php declare(strict_types=1);
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\unit;

use Duppy\DuppyServices\EnvironmentService;
use Duppy\Entities\Environment;
use Duppy\Entities\PermissionAssignment;
use PHPUnit\Framework\TestCase;

final class PermissionAssignmentTest extends TestCase {

    public function testNegativeAssignment() {
        $positive = new PermissionAssignment;
        $negative = new PermissionAssignment;

        $positive->setPermission("some.permission.type");
        $negative->setPermission("-another.permission.type");

        $this->assertSame(true, $positive->getPermissionEval());
        $this->assertSame(false, $negative->getPermissionEval());
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

        $this->assertSame(true, $noEnvPerm->inThisEnvironment());
        $this->assertSame(true, $thisEnvPerm->inThisEnvironment());
        $this->assertSame(false, $otherEnvPerm->inThisEnvironment());
    }

}