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

}