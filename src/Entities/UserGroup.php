<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Duppy\Bootstrapper\DCache;
use Duppy\Util;

/**
 * UserGroup Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="user_groups")
 */
class UserGroup {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string")
     */
    protected string $name;

    /**
     * @ORM\Column(type="integer")
     */
    protected int $weight;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $colour;

    /**
     * @ORM\ManyToMany(targetEntity="WebUser", mappedBy="groups")
     */
    protected ArrayCollection $users;

    /**
     * @ORM\OneToMany(targetEntity="UserGroup", mappedBy="parent")
     */
    protected ArrayCollection $children;

    /**
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected ?UserGroup $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="PermissionAssignment", mappedBy="groups")
     * @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     */
    protected ArrayCollection $permissions;

    /**
     * Cached generated permissions
     * Use getPermissions to generate it
     *
     * @var DCache
     */
    protected DCache $generatedPermissions;

    public function __construct() {
        $this->users = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->permissions = new ArrayCollection();
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

    /**
     * @param string $name
     */
    public function setName(string $name) {
        $this->name = $name;
    }

    /**
     * @param int $weight
     */
    public function setWeight(int $weight) {
        $this->weight = $weight;
    }

    /**
     * @param string $colour
     */
    public function setColour(string $colour) {
        $this->colour = $colour;
    }

    /**
     * @param UserGroup|null $parent
     */
    public function setParent(?UserGroup $parent) {
        $this->parent = $parent;
    }

    /**
     * @param PermissionAssignment $perm
     */
    public function addPermission(PermissionAssignment $perm) {
        $this->permissions->add($perm);
    }

    /**
     * @param PermissionAssignment $perm
     */
    public function removePermission(PermissionAssignment $perm) {
        $this->permissions->removeElement($perm);
    }

    /**
     * Recursive function to fetch all nested parents of this group
     *
     * @param array $parents
     * @return array
     */
    public function getParents(array $parents = []): array {
        if (($key = array_key_last($parents)) !== null) {
            $group = $parents[$key];
        } else {
            $group = $this;
        }

        if (!Util::is($group, UserGroup::class)) {
            return $parents;
        }

        $newParent = $group->get("parent");

        if ($newParent == null || !Util::is($newParent, UserGroup::class)) {
            return $parents;
        }

        $parents[] = $newParent;

        return $group->getParents($parents);
    }

    /**
     * @return ArrayCollection
     */
    public function getNonInheritedPermissions(): ArrayCollection {
        return $this->permissions;
    }

    /**
     * Get permissions for a group
     * This generates a full array of permissions based on this groups inheritance.
     *
     * @param bool $dictionary
     * @return array
     */
    public function getPermissions(bool $dictionary = true): array {
        if (($perms = $this->generatedPermissions->get()) != null) {
            if (!$dictionary) {
                return Util::boolDictToNormal($perms);
            }

            return $perms;
        }

        $perms = [];

        $parents = $this->getParents();
        $parents = array_reverse($parents);

        $parents[] = $this;

        foreach ($parents as $parent) {
            $groupPerms = $parent->getNonInheritedPermissions();

            foreach ($groupPerms as $perm) {
                $key = $perm->getPermission();
                $eval = $perm->getPermissionEval();

                if (!$perm->inThisEnvironment()) {
                    continue;
                }

                $perms[$key] = $eval;
            }
        }

        $this->generatedPermissions->setObject($perms);

        if (!$dictionary) {
            return Util::boolDictToNormal($perms);
        }

        return $perms;
    }

    /**
     * Returns if the user has the permission or not
     * @param string $perm
     * @return bool
     */
    public function hasPermission(string $permission): bool {
        $perms = $this->getPermissions();
        return Util::evaluatePermissionDict($perms, $permission);
    }

}