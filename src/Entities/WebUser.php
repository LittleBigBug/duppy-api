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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Duppy\DuppyException;
use Duppy\DuppyServices\Settings;
use Duppy\DuppyServices\TokenManager;
use Duppy\DuppyServices\UserService;
use Duppy\Util;
use JsonSerializable;

/**
 * WebUser Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="web_users")
 */
class WebUser implements JsonSerializable {

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
    protected $providerAuths;

    /**
     * @ORM\Column(type="string")
     */
    protected string $currentSessionCrumb = "";

    /**
     * @ORM\ManyToMany(targetEntity="UserGroup", inversedBy="users")
     * @ORM\JoinTable(name="web_user_group_map")
     */
    protected $groups;

    /**
     * @ORM\OneToMany(targetEntity="Ban", inversedBy="user")
     * @ORM\JoinColumn(name="ban_id", referencedColumnName="id")
     */
    protected $bans;

    /**
     * @ORM\OneToMany(targetEntity="PermissionAssignment", mappedBy="user")
     * @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     */
    protected $permissions;

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
     * @var array
     */
    protected array $generatedPermissions;

    public function __construct() {
        $this->providerAuths = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->bans = new ArrayCollection();
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
    public function get(string $property) {
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
     * Returns true if this user's weight is bigger than $otherUser
     *
     * @param WebUser $otherUser
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function weightCheck(WebUser $otherUser): bool {
        $oWeight = $otherUser->getWeight();
        $myWeight = $this->getWeight();

        $eq = (new Settings)->inst()->getSetting("equalWeightPasses") && ($myWeight >= $oWeight);
        return $eq || $myWeight > $oWeight;
    }

    /**
     * Gets users permissions, in an array
     * Specify it to return a dictionary with $dictionary
     *
     * This generates the full permissions array for this user based on their groups and their inheritance.
     * The array is cached to only run once per connection
     *
     * @param bool $dictionary
     * @return array
     */
    public function getPermissions(bool $dictionary = true): array{
        // Return generated permissions for this session if there are some to save processing power
        if ($this->generatedPermissions != null && sizeof($this->generatedPermissions) > 0) {
            if (!$dictionary) {
                return Util::boolDictToNormal($this->generatedPermissions);
            }

            return $this->generatedPermissions;
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
                $ind = $perm->getPermission();
                $eval = $perm->getPermissionEval();

                if (!$perm->inThisEnvironment()) {
                    continue;
                }

                $perms[$ind] = $eval;
            }
        }

        foreach ($this->permissions as $perm) {
            $ind = $perm->getPermission();

            if (!$perm->inThisEnvironment()) {
                continue;
            }

            $perms[$ind] = $perm->getPermissionEval();
        }

        $this->generatedPermissions = $perms;

        // Convert to regular table
        // Its faster to do this in the case of searching thru the table if the eval is false.
        if ($dictionary) {
            return Util::boolDictToNormal($this->generatedPermissions);
        }

        return $perms;
    }

    /**
     * If the user has the permission or not
     *
     * @param $perm
     * @return bool
     */
    public function hasPermission($perm): bool {
        $perms = $this->getPermissions();
        return $perms[$perm] == true;
    }

    /**
     * If the user is the current logged in user
     *
     * @return bool
     */
    public function isMe(): bool {
        $authToken = (new TokenManager)->inst()->getAuthToken();

        if ($authToken == null || !array_key_exists("id", $authToken)) {
            return false;
        }

        return $this->get("id") == $authToken["id"];
    }

    /**
     * Check if the user has an active global ban (Uses UserService)
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function globalBanned(): bool {
        return (new UserService)->inst()->checkGlobalBan($this);
    }

    /**
     * @return bool
     */
    public function hasDirectGlobalBan(): bool {
        foreach ($this->bans as $ban) {
            if ($ban->isGlobal()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Ban[]
     */
    public function getActiveBans(): array {
        $bans = [];

        foreach ($this->bans as $ban) {
            if (!$ban->isActive()) { continue; }
            $bans[] = $ban;
        }

        return $bans;
    }

    /**
     * Check if the user has an active ban within the environment
     *
     * @return bool
     */
    public function environmentBanned(): bool {
        foreach ($this->bans as $ban) {
            if (!$ban->isActive() || !$ban->inThisEnvironment()) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * If the user is banned at all (within the current environment or globally)
     *
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function banned(): bool {
        return $this->environmentBanned() || $this->globalBanned();
    }

    /**
     * If the user has any permanent bans.
     *
     * @return bool
     */
    public function permaBanned(): bool {
        foreach ($this->getActiveBans() as $ban) {
            if ($ban->isPermanent()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the date when this user's ban will be lifted. (In this environment, not global)
     * Will return null if the user is permanently banned (or has no ban)
     *
     * @return ?DateTime
     */
    public function unbanTime(): ?DateTime {
        if ($this->permaBanned()) {
            return null;
        }

        $date = null;
        $bans = $this->getActiveBans();

        foreach ($bans as $ban) {
            $expiry = $ban->get("expiry");

            // Use this ban's date if it is within the current environment (not global)
            // and only use it if its bigger (or the first) than the stored $date (latest they can be unbanned)
            if ($ban->inThisEnvironment() && !$ban->isGlobal() && ($date == null || $expiry > $date)) {
                $date = $expiry;
            }
        }

        return $date;
    }

    /**
     * Gets the date when this user's ban will be lifted. (Globally)
     * Will return null if the user is permanently banned (or has no ban)
     *
     * @return ?DateTime
     */
    public function globalUnbanTime(): ?DateTime {

    }

    /**
     * Serializes the user into a basic array of info
     *
     * @return array
     */
    public function jsonSerialize(): array {
        return (new UserService)->inst()->getBasicInfo($this);
    }

}
