<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\unit\Entities;

use Duppy\DuppyServices\EnvironmentService;
use Duppy\Entities\Environment;
use Duppy\Entities\GroupAssignment;
use Duppy\Tests\Tools\DuppyTestCase;

class GroupAssignmentTest extends DuppyTestCase {

    public function testEnvironmentAssignment() {
        $env = new Environment;
        $oEnv = new Environment;

        $env->setEnabled(true);
        $env->setName("test-environment");

        $oEnv->setEnabled(true);
        $oEnv->setName("other-environment");

        (new EnvironmentService)->inst()->setEnvironment($env);

        $noEnvGroup = new GroupAssignment;
        $thisEnvGroup = new GroupAssignment;
        $otherEnvGroup = new GroupAssignment;

        $thisEnvGroup->setEnvironment($env);
        $otherEnvGroup->setEnvironment($oEnv);

        $this->assertSameA(true, $noEnvGroup->inThisEnvironment());
        $this->assertSameA(true, $thisEnvGroup->inThisEnvironment());
        $this->assertSameA(false, $otherEnvGroup->inThisEnvironment());
    }

}