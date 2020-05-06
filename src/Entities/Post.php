<?php
namespace Duppy\Entities;

use Ramsey\Uuid\Doctrine\UuidGenerator;
use Duppy\Abstracts\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

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
    protected $uuid;

    /**
     * @ORM\Column(type="string")
     */
    protected string $content;
}