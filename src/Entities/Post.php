<?php
namespace Duppy\Entities;

use Ramsey\Uuid\Doctrine\UuidGenerator;
use Duppy\Abstracts\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * Post Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="posts")
 */
class Post extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    protected UuidInterface $uuid;

    /**
     * @ORM\Column(type="string")
     */
    protected string $content;

    /**
     * @ORM\Column(type="integer")
     */
    protected int $thread_id;

    /**
     * @ORM\Column(type="string", length=17)
     */
    protected string $author_id;

    /**
     * @ORM\ManyToOne(targetEntity="Thread", inversedBy="posts")
     * @ORM\JoinColumn(name="thread_id", referencedColumnName="id")
     */
    protected $thread;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser", inversedBy="posts")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="steamid64")
     */
    protected $webuser;
}