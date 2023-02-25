<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation as WDS;

#[ORM\Table(name: 'test_article')]
#[ORM\Entity()]
#[WDS\Accessible()]
final class Article implements ApiEntityInterface
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[WDS\Listable(default: true)]
    private int $id;

    #[ORM\Column(name: 'title', type: 'string', nullable: false)]
    #[WDS\Listable(default: true)]
    #[WDS\Creatable()]
    #[WDS\Updatable()]
    private string $title;

    #[ORM\ManyToOne(targetEntity: Author::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[WDS\Listable()]
    #[WDS\Creatable()]
    #[WDS\Updatable()]
    private ?Author $author = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[WDS\Listable()]
    #[WDS\Creatable()]
    #[WDS\Updatable()]
    private ?Category $category = null;

    public function __construct(int $id, string $title, ?Author $author, ?Category $category)
    {
        $this->id = $id;
        $this->title = $title;
        $this->author = $author;
        $this->category = $category;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }
}
