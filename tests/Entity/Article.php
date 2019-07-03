<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Entity;

use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;

final class Article implements ApiEntityInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $title;

    /** @var Author|null */
    private $author;

    /** @var Category|null */
    private $category;

    /**
     * Article constructor.
     *
     * @param int           $id
     * @param string        $title
     * @param Author|null   $author
     * @param Category|null $category
     */
    public function __construct(int $id, string $title, ?Author $author, ?Category $category)
    {
        $this->id = $id;
        $this->title = $title;
        $this->author = $author;
        $this->category = $category;
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
     * @return Author|null
     */
    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    /**
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }
}
