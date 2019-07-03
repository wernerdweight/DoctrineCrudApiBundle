<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;

class Category implements ApiEntityInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $title;

    /** @var ArrayCollection|PersistentCollection */
    private $articles;

    /**
     * Category constructor.
     * @param int $id
     * @param string $title
     * @param ArrayCollection|PersistentCollection $articles
     */
    public function __construct(int $id, string $title, Collection $articles)
    {
        $this->id = $id;
        $this->title = $title;
        $this->articles = $articles;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }
}
