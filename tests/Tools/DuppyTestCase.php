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
            return $object->with($this->any())->willReturn($findByReturn);
        };

        $defaultReturnSingle = function(InvocationMocker $object) use ($findByReturn) {
            return $object->with($this->any())->willReturn($findByReturn[0]);
        };

        $repoMethods["findBy"] = $defaultReturn;
        $repoMethods["findOneBy"] = $defaultReturnSingle;

        if ($repoMockEnt != null) {
            $t = array_keys($repoMethods);
            $repoMock = $this->getMockBuilder(EntityRepository::class)
                ->disableOriginalConstructor()
                ->onlyMethods(array_unique(array_merge([
                    "findBy",
                    "findOneBy",
                ], $t)))->getMock();

            $dboMethods["getRepository"] = function (InvocationMocker $object) use ($repoMockEnt, $repoMock) {
                $object->with($repoMockEnt)->willReturn($repoMock);
            };
        }

        $dboMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_keys($dboMethods))->getMock();

        foreach ($dboMethods as $key => $method) {
            $inv = $dboMock->expects($this->any())->method($key);

            $method($inv);
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