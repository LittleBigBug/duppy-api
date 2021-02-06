<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;
use Duppy\DuppyServices\EnvironmentService;
use JetBrains\PhpStorm\Pure;

/**
 * GroupAssignment Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="group_assignment")
 */
class GroupAssignment {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Environment")
     */
    protected Environment $environment;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser", inversedBy="groups")
     */
    protected WebUser $user;

    /**
     * Returns if the group is valid in this environment
     * @return bool
     */
    #[Pure]
    public function inThisEnvironment(): bool {
        $environment = (new EnvironmentService)->inst()->getEnvironment();

        if ($environment == null) {
            return true;
        }

        return $environment === $this->environment;
    }

    /**
     * @param Environment $environment
     */
    public function setEnvironment(Environment $environment) {
        $this->environment = $environment;
    }

    /**
     * @param WebUser $user
     */
    public function setUser(WebUser $user) {
        $this->user = $user;
    }

    // Each entity class needs their own version of this function so that doctrine knows to use it for lazy-loading
    /**
     * Return a property
     *
     * @param string $property
     * @return mixed
     */
    public function get(string $property) {
        return $this->$property;
    }

}