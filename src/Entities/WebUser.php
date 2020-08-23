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
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $thirdpartyAuth;

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
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $bio;

    /**
     * @ORM\OneToMany(targetEntity="Post", mappedBy="webuser")
     */
    protected $posts;

    /**
     * @ORM\OneToMany(targetEntity="Thread", mappedBy="webuser")
     */
    protected $threads;
}
