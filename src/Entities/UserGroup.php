<?php

namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;
use Duppy\Abstracts\AbstractEntity;
use Duppy\Util;

/**
 * UserGroup Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="user_groups")
 */
class UserGroup extends AbstractEntity {

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
    protected $users;

    /**
     * @ORM\OneToMany(targetEntity="UserGroup", mappedBy="parent")
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @ORM\OneToMany(targetEntity="PermissionAssignment", mappedBy="groups")
     * @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     */
    protected $permissions;

    /**
     * Cached Dictionary array of full generated settings.
     * Use getPermissions to generate it
     *
     * @var array
     */
    protected array $generatedPermissions;

    /**
     * Recursive function to fetch all nested parents of this group
     *
     * @param array $parents
     * @return array
     */
    public function getParents(array $parents = []): array {
        $parent = $this;

        if ($key = array_key_last($parents) != null) {
            $parent = $parents[$key];
        }

        if (!is_subclass_of($parent, "Duppy\Entities\UserGroup")) {
            return $parents;
        }

        $newParent = $parent->get("parent");
        $parents[] = $newParent;

        return $this->getParents($parents);
    }

    public function getNonInheritedPermissions() {
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
        // Return generated permissions for this session if there are some to save processing power
        if ($this->generatedPermissions != null && sizeof($this->generatedPermissions) > 0) {
            if (!$dictionary) {
                return Util::boolDictToNormal($this->generatedPermissions);
            }

            return $this->generatedPermissions;
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

                $perms[$key] = $eval;
            }
        }

        return $perms;
    }

    public function hasPermission(string $perm): bool {
        $perms = $this->getPermissions();
        return $perms[$perm] == true;
    }

}