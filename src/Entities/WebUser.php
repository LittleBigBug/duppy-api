<?php
namespace Duppy\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Duppy\Abstracts\AbstractEntity;
use Duppy\Bootstrapper\TokenManager;
use Duppy\Util;

/**
 * WebUser Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="web_users")
 */
class WebUser extends AbstractEntity {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string")
     */
    protected string $username;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $password;

    /**
     * @ORM\OneToMany(targetEntity="WebUserProviderAuth", mappedBy="user")
     */
    protected $providerAuths;

    /**
     * @ORM\ManyToMany(targetEntity="UserGroup", inversedBy="users")
     * @ORM\JoinTable(name="web_user_group_map")
     */
    protected $groups;

    /**
     * @ORM\OneToMany(targetEntity="PermissionAssignment", mappedBy="users")
     * @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     */
    protected $permissions;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $avatarUrl;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $bio;

    /**
     * Cached generated permissions with groups/inheritance
     *
     * @var array
     */
    protected array $generatedPermissions;

    public function __construct(array $data) {
        parent::__construct($data);

        $this->providerAuths = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function setEmail(string $email) {
        $this->email = $email;
    }

    public function setPassword(string $password) {
        $this->password = $password;
    }

    public function setUsername(string $username) {
        $this->username = $username;
    }

    public function setAvatarUrl(string $url) {
        $this->avatarUrl = $url;
    }

    public function addProviderAuth(WebUserProviderAuth $providerAuth) {
        $this->providerAuths->add($providerAuth);
    }

    public function addGroup(UserGroup $group) {
        $this->groups->add($group);
    }

    public function addPermission(PermissionAssignment $perm) {
        $this->permissions->add($perm);
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
           $aW = $a->getWeight();
           $bW = $b->getWeight();

           return $aW <=> $bW;
        });

        foreach ($groups as $group) {
            $groupPerms = $group->getPermissions();

            foreach ($groupPerms as $perm) {
                $ind = $perm->getPermission();
                $eval = $perm->getPermissionEval();

                $perms[$ind] = $eval;
            }
        }

        foreach ($this->permissions as $perm) {
            $ind = $perm->getPermission();
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
        $authToken = TokenManager::getAuthToken();

        if ($authToken == null || !array_key_exists("id", $authToken)) {
            return false;
        }

        return $this->get("id") == $authToken["id"];
    }

}
