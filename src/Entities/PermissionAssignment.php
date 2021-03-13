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
 * Setting Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="permission_assignment")
 */
class PermissionAssignment {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * Permission node to assign
     *
     * @ORM\GeneratedValue
     * @ORM\Column(type="string", nullable=false)
     */
    protected string $permission = "";

    /**
     * @ORM\ManyToOne(targetEntity="Environment")
     */
    protected ?Environment $environment = null;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser", inversedBy="permissions")
     */
    protected ?WebUser $user = null;

    /**
     * @ORM\ManyToOne(targetEntity="ApiClient", inversedBy="permissions")
     */
    protected ?ApiClient $apiClient = null;

    /**
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="permissions")
     */
    protected ?UserGroup $group = null;

    /**
     * Returns the permission string without any modifiers
     *
     * @return string
     */
    public function getPermission(): string {
        if (str_starts_with($this->permission, "-")) {
            return substr($this->permission, 1);
        }

        return $this->permission;
    }

    /**
     * Returns if the permission string with modifiers is additive
     *
     * @return bool
     */
    public function getPermissionEval(): bool {
        return !str_starts_with($this->permission, "-");
    }

    /**
     * Returns if the permission is valid in this environment
     * @return bool
     */
    #[Pure]
    public function inThisEnvironment(): bool {
        $envService = (new EnvironmentService)->inst();
        $environment = $envService->getEnvironment();
        return $envService->compareEnvironment($environment, $this->environment);
    }

    /**
     * @param string $perm
     */
    public function setPermission(string $perm) {
        $this->permission = $perm;
    }

    /**
     * @param WebUser $user
     */
    public function setUser(WebUser $user) {
        $this->user = $user;
    }

    /**
     * @param ApiClient $apiClient
     */
    public function setApiClient(ApiClient $apiClient) {
        $this->apiClient = $apiClient;
    }

    /**
     * @param UserGroup $group
     */
    public function setGroup(UserGroup $group) {
        $this->group = $group;
    }

    /**
     * @param Environment $environment
     */
    public function setEnvironment(Environment $environment) {
        $this->environment = $environment;
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