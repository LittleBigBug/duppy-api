<?php
namespace Duppy\Entities;

use Duppy\Abstracts\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

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

    // TODO: workout datetimes
    // TODO: add other models + workout uuids
}