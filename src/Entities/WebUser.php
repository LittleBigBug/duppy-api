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
     * @ORM\OneToMany(targetEntity="WebUserProviderAuth", mappedBy="webuser")
     */
    protected $providerAuths;

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
