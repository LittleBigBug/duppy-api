<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\Tools;

use DI\Container;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Duppy\Bootstrapper\Bootstrapper;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DuppyTestCase extends TestCase {

    /**
     * Injects the DI with the mock database given
     *
     * @param EntityManager $db
     */
    public function databaseTest(EntityManager $db) {
        Bootstrapper::$container = new Container;
        Bootstrapper::$container->set("database", $db);
    }

    /**
     * Doctrine ORM Mock builder
     *
     * @param string|null $repoMockEnt
     * @param array $findByReturn
     * @param array $dboMethods
     * @param array $repoMethods
     * @return EntityManager|MockObject
     */
    public function doctrineMock(?string $repoMockEnt = null, array $findByReturn = [], array $dboMethods = [], array $repoMethods = []): EntityManager|MockObject {
        $defaultReturn = function(InvocationMocker $object) use ($findByReturn) {
            return $object->with($this->any())
                    ->will($this->returnValue($findByReturn));
        };

        $defaultReturnSingle = function(InvocationMocker $object) use ($findByReturn) {
            return $object->with($this->any())
                ->will($this->returnValue($findByReturn[0]));
        };

        $repoMethods[] = [
            "findBy" => $defaultReturn,
            "findOneBy" => $defaultReturnSingle,
        ];

        if ($repoMockEnt != null) {
            $repoMock = $this->getMockBuilder(EntityRepository::class)
                ->disableOriginalConstructor()
                ->onlyMethods(array_merge([
                    "findBy",
                    "findOneBy",
                ], $repoMethods))->getMock();

            $thisCl = $this;

            $dboMethods[] = [
                "getRepository" => function (InvocationMocker $object) use ($thisCl, $repoMockEnt, $repoMock) {
                    $object->with($repoMockEnt)
                        ->will($thisCl->returnValue($repoMock));
                },
            ];
        }
        $dboMethodNames[] = "getRepository";

        $dboMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods($dboMethodNames)->getMock();

        foreach ($dboMethods as $key => $method) {
            $dboMock->expects($this->any())
                ->method($key);

            return $method($dboMock);
        }

        return $dboMock;
    }

}