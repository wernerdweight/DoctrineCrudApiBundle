<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation as WDS;

/**
 * Article.
 *
 * @ORM\Table(name="test_article")
 * @ORM\Entity()
 * @WDS\Accessible()
 */
final class Article implements ApiEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", nullable=false)
     * @WDS\Listable(default=true)
     * @WDS\Creatable()
     * @WDS\Updatable()
     */
    private $title;

    /**
     * @var Author|null
     *
     * @ORM\ManyToOne(targetEntity="Author")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     * @WDS\Listable()
     * @WDS\Creatable()
     * @WDS\Updatable()
     */
    private $author;

    /**
     * @var Category|null
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="articles")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     * @WDS\Listable()
     * @WDS\Creatable()
     * @WDS\Updatable()
     */
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