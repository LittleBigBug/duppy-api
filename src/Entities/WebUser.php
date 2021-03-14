<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use DateTime;
use DI\DependencyException;
use DI\NotFoundException;
use Duppy\DuppyException;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Duppy\Abstracts\DuppyUser;
use Duppy\DuppyServices\UserService;
use Duppy\Bootstrapper\DCache;
use Duppy\Util;

/**
 * WebUser Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="web_users")
 */
class WebUser extends DuppyUser implements JsonSerializable {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $username = "";

    /**
     * @ORM\Column(type="string")
     */
    protected string $email = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $password = "";

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
    protected DateTime $created;

    /**
     * @ORM\OneToMany(targetEntity="WebUserProviderAuth", mappedBy="user")
     */
    protected ArrayCollection $providerAuths;

    /**
     * @ORM\Column(type="string")
     */
    protected string $currentSessionCrumb = "";

    /**
     * @ORM\ManyToMany(targetEntity="UserGroup", inversedBy="users")
     * @ORM\JoinTable(name="web_user_group_map")
     */
    protected ArrayCollection $groups;

    /**
     * @ORM\OneToMany(targetEntity="Ban", mappedBy="user")
     * @ORM\JoinColumn(name="ban_id", referencedColumnName="id")
     */
    protected ArrayCollection $bans;

    /**
     * @ORM\OneToMany(targetEntity="PermissionAssignment", mappedBy="user")
     * @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     */
    protected ArrayCollection $permissions;

    /**
     * @ORM\Column(type="string")
     */
    protected string $avatarUrl = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $bio = "";

    /**
     * Cached generated permissions with groups/inheritance
     *
     * @var DCache
     */
    protected DCache $generatedPermissions;

    #[Pure]
    public function __construct() {
        $this->providerAuths = new ArrayCollection;
        $this->groups = new ArrayCollection;
        $this->permissions = new ArrayCollection;
        $this->bans = new ArrayCollection;

        $this->generatedPermissions = new DCache;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email) {
        $this->email = $email;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password) {
        $this->password = $password;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username) {
        $this->username = $username;
    }

    /**
     * @param string $url
     */
    public function setAvatarUrl(string $url) {
        $this->avatarUrl = $url;
    }

    /**
     * @param string $crumb
     */
    public function setCrumb(string $crumb) {
        $this->currentSessionCrumb = $crumb;
    }

    /**
     * @param WebUserProviderAuth $providerAuth
     */
    public function addProviderAuth(WebUserProviderAuth $providerAuth) {
        $this->providerAuths->add($providerAuth);
    }

    /**
     * @param UserGroup $group
     */
    public function addGroup(UserGroup $group) {
        $this->groups->add($group);
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

    // Each entity class needs their own version of this function so that doctrine knows to use it for lazy-loading
    /**
     * Return a property
     *
     * @param string $property
     * @return mixed
     */
    #[Pure]
    public function get(string $property): mixed {
        return $this->$property;
    }

    /**
     * ID Setter
     * This is an internal testing function and can be used elsewhere, but shouldn't.
     *
     * @param int $id
     */
    public function setId(int $id) {
        $this->id = $id;
    }

    /**
     * Returns the user's weight. This is the highest weight out of any of their ranks
     *
     * @return integer
     */
    #[Pure]
    public function getWeight(): int {
        $mx = 0;

        foreach ($this->get("groups") as $group) {
            $w = $group->get("weight");

            if ($w > $mx) {
                $mx = $w;
            }
        }

        return $mx;
    }

    /**
     * Gets users permissions, in an array
     * Specify it to return a dictionary with $dictionary (default is true)
     *
     * This generates the full permissions array for this user based on their groups and their inheritance.
     * The array is cached to only run once per connection
     *
     * @param bool $dictionary
     * @return array
     */
    #[Pure]
    public function getPermissions(bool $dictionary = true): array {
        // Return generated permissions for this session if there are some to save processing power
        if (($perms = $this->generatedPermissions->get()) != null) {
            if (!$dictionary) {
                return Util::boolDictToNormal($perms);
            }

            return $perms;
        }

        $perms = [];
        $groups = $this->get("groups");

        // Sort groups by weight ascending to apply heaviest last
        usort($groups, function($a, $b) {
           $aW = $a->get("weight");
           $bW = $b->get("weight");

           return $aW <=> $bW;
        });

        foreach ($groups as $group) {
            $groupPerms = $group->getPermissions();

            foreach ($groupPerms as $perm) {
                if (!$perm->inThisEnvironment()) {
                    continue;
                }

                $ind = $perm->getPermission();
                $eval = $perm->getPermissionEval();

                $perms[$ind] = $eval;
            }
        }

        foreach ($this->permissions as $perm) {
            if (!$perm->inThisEnvironment()) {
                continue;
            }

            $ind = $perm->getPermission();
            $perms[$ind] = $perm->getPermissionEval();
        }

        $this->generatedPermissions->setObject($perms);

        // Convert to regular table
        // Its faster to do this in the case of searching thru the table if the eval is false.
        if (!$dictionary) {
            return Util::boolDictToNormal($perms);
        }

        return $perms;
    }

    /**
     * @return PermissionAssignment[]
     */
    #[Pure]
    public function getExplicitPermissions(): array {
        return $this->permissions->toArray();
    }

    /**
     * If the user has the permission or not
     *
     * @param string $permission
     * @return bool
     */
    #[Pure]
    public function hasPermission(string $permission): bool {
        $perms = $this->getPermissions();
        return Util::evaluatePermissionDict($perms, $permission);
    }

    /**
     * @return Ban[]
     */
    #[Pure]
    public function getActiveBans(): array {
        $bans = [];

        foreach ($this->bans as $ban) {
            if (!$ban->isActive()) { continue; }
            $bans[] = $ban;
        }

        return $bans;
    }

    /**
     * Serializes the user into a basic array of info
     *
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function jsonSerialize(): array {
        return (new UserService)->inst()->getBasicInfo($this);
    }

}
