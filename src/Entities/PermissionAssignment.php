<?php

namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\Column(type="integer", nullable=false)
     */
    protected string $permission;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser", inversedBy="permissions")
     */
    protected $users;

    /**
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="permissions")
     */
    protected $groups;

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

    public function getUsers() {
        return $this->users;
    }

    public function getGroups() {
        return $this->groups;
    }

}