<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\DuppyServices;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractService;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Entities\Environment;

final class EnvironmentService extends AbstractService {

    protected ?Environment $currentEnvironment;

    /**
     * @param Environment|null $environment
     */
    public function setEnvironment(?Environment $environment = null) {
        $this->currentEnvironment = $environment;
    }

    /**
     * @return Environment|null
     */
    public function getEnvironment(): ?Environment {
        return $this->currentEnvironment;
    }

    /**
     * Returns if the string is a valid environment or not and returns the environment
     *
     * @param string $environment
     * @return Environment|bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function checkEnvironment(string $environment): Environment|bool {
        $dbo = Bootstrapper::getContainer()->get("database");
        $repo = $dbo->getRepository("Duppy\Entities\Environment");

        $environment = $repo->findOneBy([ "name" => $environment, "enabled" => true, ]);
        return $environment == null ? false : $environment;
    }

}