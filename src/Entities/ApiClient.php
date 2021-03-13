<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Duppy\Abstracts\AbstractApiClientCustomCheck;
use Duppy\Abstracts\DuppyUser;
use Duppy\Bootstrapper\DCache;
use Duppy\DuppyServices\UserService;
use Duppy\Util;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;

/**
 * ApiClient Entity
 *
 * Abstract entity that allows authoritative logins to the API directly without a JWT Authorization
 * 
 * Users can create separate APIClients that associate to them and use their permissions, 
 * additionally allowing the user to specify what permissions to allow.
 *
 * @ORM\Entity
 * @ORM\Table(name="apiclients")
 */
class ApiClient extends DuppyUser implements JsonSerializable {

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected string $clientId;

    /**
     * Authentication Method
     * Default is "token" below but you can use "custom"
     * @ORM\Column(type="string")
     */
    protected string $method;

    /**
     * Authentication Token (hashed as a password)
     * Only used when the above method is null or 'token'
     *
     * This is a cleartext string as a function if the above method is "custom".
     * The method should accept one argument (the token passed) and return a bool if it is successful.
     * @ORM\Column(type="string")
     */
    protected string $token;

    /**
     * Specific permissions
     *
     * @ORM\OneToMany(targetEntity="PermissionAssignment", mappedBy="user")
     * @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     */
    protected ArrayCollection $permissions;

    /**
     * If the APIClient has access to all permissions the associated user has
     * 
     *  @ORM\Column(type="bool")
     */
    protected bool $allPerms = false;

    /**
     * Associated user with the API Client. If null, owned by the system.
     *
     * @ORM\ManyToOne(targetEntity="WebUser", nullable=true)
     */
    protected ?WebUser $associatedUser = null;

    /**
     * Cached generated permissions with masked permissions
     *
     * @var DCache
     */
    protected DCache $generatedPerms;

    /**
     * If the APIClient is a "Super" or doesn't really have an owner or associated user but acts as the system
     * 
     * @ORM\Column(type="bool")
     */
    protected bool $isSuper = false;

    public function __construct() {
        $this->permissions = new ArrayCollection;
        $this->generatedPerms = new DCache;
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
     * @param string $clientId
     */
    public function setClientId(string $clientId) {
        $this->clientId = $clientId;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method) {
        $this->method = $method;
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
     * Sets if the APIClient has all permissions the associated user has
     * 
     * @param bool $allPerms = false
     */
    public function setAllPerms(bool $allPerms = true) {
        $this->allPerms = $allPerms;
    }

    /**
     * Default mode 'token':
     * Hashes a raw token and sets it
     *
     * Mode 'custom':
     * Uses a class name string Duppy\Class\Name and invokes it (__invoke)
     *
     * Returns its success
     * @param string $token
     * @return bool
     */
    public function setToken(string $token): bool {
        $set = $token;

        if ($this->method == null || $this->method == "token") {
            $hash = password_hash($token, PASSWORD_DEFAULT);

            if ($hash == false) {
                return false;
            }

            $set = $hash;
        }

        $this->token = $set;
        return true;
    }

    /**
     * @param WebUser $user
     */
    public function setAssociatedUser(WebUser $user) {
        $this->associatedUser = $user;
    }

    /**
     * Verifies a raw token against the stored hash
     * Returns its success
     * @param string $oToken
     * @return bool
     */
    #[Pure]
    public function checkToken(string $oToken): bool {
        if ($this->method != null) {
            switch ($this->method) {
                case "custom":
                    $className = $this->token;

                    if (!is_subclass_of($className, AbstractApiClientCustomCheck::class)) {
                        return false;
                    }

                    $classInst = new $className;
                    return $classInst($oToken);
            }
        }

        // Default to Token style (hashed)
        return password_verify($oToken, $this->token);
    }

    /**
     * Returns the associated webuser/owner of this APIClient
     * Returns true if a superuser
     * 
     * @return WebUser|bool|null
     */
    public function getOwner(): WebUser|bool|null {
        $user = $this->get("associatedUser");

        if ($user == null) {
            if ($this->get("isSuper") === true) {
                return true;
            }

            return null;
        }
        
        return $user;
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool {
        $perms = $this->getPermissions();
        return Util::evaluatePermissionDict($perms, $permission);
    }

    /**
     * Returns an array of generated permissions (inherited, masked with inherited permissions)
     * 
     * @param bool $dictionary = true
     * @return array
     */
    public function getPermissions(bool $dictionary = true): array {
        if (($perms = $this->generatedPerms->get()) != null) {
            if (!$dictionary) {
                return Util::boolDictToNormal($perms);
            }

            return $perms;
        }

        $owner = $this->getOwner();

        // Missing owners have no permissions
        if ($owner == null) {
            return $this->generatedPerms->setObject([]);
        }

        $assocUserPerms = [];
        $isSuper = $this->isSuper;

        // $owner can be 'true' here if its a null user but a super user
        if ($owner instanceof WebUser) {
            $assocUserPerms = $owner->getPermissions(); // Fetch user dictionary permissions (inherited)

            // No permissions, don't bother
            if (empty($assocUserPerms)) {
                return $this->generatedPerms->setObject([]);
            }
        } elseif ($isSuper) {
            // Supers have every permission
            $assocUserPerms["*"] = true;
        }

        // APIClient has all associated user permissions
        $hasAllPermissions = $this->allPerms; // Has all 'assocUserPerms'

        // Just return the user permissions if they have everything
        if ($hasAllPermissions) {
            return $this->generatedPerms->setObject($assocUserPerms);
        }

        // Get all applied permissions and check if the user has access to them and apply only those to the APIClient
        $allowedPerms = $this->getExplicitPermissions();
        $genPerms = [];

        foreach ($allowedPerms as $perm) {
            // Skip if the permission is not allowed or not in this environment
            if (!$perm->inThisEnvironment() || !$perm->getPermissionEval()) {
                continue;
            }

            $permStr = $perm->getPermission();
            $userHas = $this->isSuper ?? Util::evaluatePermissionDict($assocUserPerms, $permStr);

            if ($userHas) {
                $genPerms[$permStr] = true;
            }
        }

        if (!$dictionary) {
            return Util::boolDictToNormal($genPerms);
        }

        return $this->generatedPerms->setObject($genPerms);
    }

    /**
     * Returns an array of permissions allowed/assigned to the user
     * 
     * @return array
     */
    public function getExplicitPermissions(): array {
        return $this->permissions->toArray();
    }

    /**
     * Returns true if the logged in APIClient is the same or the user owns this APIClient
     * 
     * @return bool
     */
    public function isMe(): bool {
        $loggedInUser = (new UserService)->inst()->getLoggedInUser();

        if ($this->isAPIClient()) {
            $owner = $this->getOwner();

            if ($owner == $loggedInUser) {
                return true;
            }
        }

        return parent::isMe();
    }

    /**
     * Returns the APIClient's acting weight
     * 
     * @return int
     */
    public function getWeight(): int {
        $owner = $this->getOwner();

        if ($owner == true) {
            return 9999;
        } elseif (!($owner instanceof WebUser)) {
            return 0;
        }

        return $owner->getWeight();
    }

    /**
     * Returns the associated user's active bans
     * 
     * @return array
     */
    public function getActiveBans(): array {
        $owner = $this->getOwner();

        if (!($owner instanceof WebUser)) {
            return [];
        }

        return $owner->getActiveBans();
    }

    /**
     * Returns if the associated user is global banned
     * 
     * @return bool
     */
    public function globalBanned(): bool {
        $owner = $this->getOwner();

        if (!($owner instanceof WebUser)) {
            return false;
        }

        return $owner->globalBanned();
    }

    /**
     * Returns if the associated user has a direct global ban
     * 
     * @return bool
     */
    public function hasDirectGlobalBan(): bool {
        $owner = $this->getOwner();

        if (!($owner instanceof WebUser)) {
            return false;
        }

        return $owner->hasDirectGlobalBan();
    }

    public function jsonSerialize() {
        // TODO: Implement jsonSerialize() method.
    }

}