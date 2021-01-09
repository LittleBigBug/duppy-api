<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Tests\Tools;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Duppy\Bootstrapper\Bootstrapper;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class DuppyTestCase extends TestCase {

    /**
     * Detour function to automatically set the message to the line name
     *
     * @param $expected
     * @param $actual
     */
    public function assertSameA($expected, $actual) {
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $line = (string) $caller["line"];

        $this->assertSame($expected, $actual, "In-file Line: " . $line);
    }

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

    /**
     * @param array $settings
     * @throws DependencyException
     * @throws NotFoundException
     */
    function slimMock(array $settings = []) {
        $app = Bootstrapper::test();
    }

    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param array $serverParams
     * @return ServerRequestInterface
     */
    function requestMock(string $method, UriInterface|string $uri, array $serverParams = []): ServerRequestInterface {
        return (new ServerRequestFactory())->createServerRequest($method, $uri, $serverParams);
    }

}