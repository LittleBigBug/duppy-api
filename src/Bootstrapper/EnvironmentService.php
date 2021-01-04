<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Bootstrapper;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Entities\Environment;

final class EnvironmentService {

    protected static ?Environment $currentEnvironment;

    /**
     * @param Environment|null $environment
     */
    public static function setEnvironment(?Environment $environment = null) {
        EnvironmentService::$currentEnvironment = $environment;
    }

    /**
     * @return Environment|null
     */
    public static function getEnvironment(): ?Environment {
        return EnvironmentService::$currentEnvironment;
    }

    /**
     * Returns if the string is a valid environment or not and returns the environment
     *
     * @param string $environment
     * @return Environment|bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function checkEnvironment(string $environment): Environment|bool {
        $dbo = Bootstrapper::getContainer()->get("database");
        $repo = $dbo->getRepository("Duppy\Entities\Environment");

        $environment = $repo->findOneBy([ "name" => $environment, "enabled" => true, ]);
        return $environment == null ? false : $environment;
    }

}