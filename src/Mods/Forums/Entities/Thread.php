<?php
namespace Duppy\Mods\Forums\Entities;

use Duppy\Abstracts\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

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

    /**
     * @ORM\Column(type="uuid")
     */
    protected UuidInterface $category_uuid;

    /**
     * @ORM\Column(type="string", length=17)
     */
    protected string $author_id;

    /**
     * @ORM\OneToMany(targetEntity="Post", mappedBy="thread")
     */
    protected $posts;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="threads")
     * @ORM\JoinColumn(name="category_uuid", referencedColumnName="uuid")
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser", inversedBy="threads")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="steamid64")
     */
    protected $webuser;
}