<?php
namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;
use Duppy\Abstracts\AbstractEntity;

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
     * @ORM\ManyToMany(targetEntity="WebUserProviderAuth")
     * @ORM\JoinTable(name="web_user_auth_provider_map",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="providerauthconnection_id", referencedColumnName="id", unique=true)})
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

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getAvatarUrl() {
        return $this->avatarUrl;
    }

}
