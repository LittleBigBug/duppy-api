<?php
namespace Duppy\Entities;

use Duppy\Abstracts\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Thread Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="threads")
 */
class Thread extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected string $title;
}