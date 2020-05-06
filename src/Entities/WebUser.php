<?php
namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;

use Duppy\Abstracts\AbstractEntity;

/**
 * WebUser Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="web_user")
 */
class WebUser extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=17)
     */
    protected string $steamid64;

    /**
     * @ORM\Column(type="string")
     */
    protected string $username;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $bio;

    /**
     * @ORM\Column(type="string")
     */
    protected string $email;

    // TODO: add other models + workout uuids
}