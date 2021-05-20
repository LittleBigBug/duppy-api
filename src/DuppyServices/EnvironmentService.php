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

    protected ?Environment $currentEnvironment = null;

    public function clean(bool $force = false) {
        $this->currentEnvironment = null;
    }

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
        $dbo = Bootstrapper::getDatabase();
        $repo = $dbo->getRepository(Environment::class);

        $environment = $repo->findOneBy([ "name" => $environment, "enabled" => true, ]);
        return $environment == null ? false : $environment;
    }

    /**
     * Compares if the second env is the first env
     *
     * @param ?Environment $firstEnv
     * @param ?Environment $otherEnv
     * @return bool
     */
    public function compareEnvironment(?Environment $firstEnv, ?Environment $otherEnv): bool {
        $envNull = $firstEnv == null;
        $oEnvNull = $otherEnv == null;

        // In no environment, if the other env is null it will always return false
        if ($envNull && !$oEnvNull) {
            return false;
        }

        if ($oEnvNull) {
            return true;
        }

        return $firstEnv === $otherEnv;
    }

}