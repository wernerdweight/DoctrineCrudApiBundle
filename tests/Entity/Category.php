<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation as WDS;

#[ORM\Table(name: 'test_category')]
#[ORM\Entity()]
#[WDS\Accessible()]
final class Category implements ApiEntityInterface
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[WDS\Listable(default: true)]
    private int $id;

    #[ORM\Column(name: 'title', type: 'string', nullable: false)]
    #[WDS\Listable(default: true)]
    #[WDS\Creatable()]
    #[WDS\Updatable()]
    private string $title;

    /**
     * @var ArrayCollection<int, Article>|PersistentCollection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'category')]
    #[WDS\Listable()]
    #[WDS\Creatable(nested: true)]
    #[WDS\Updatable(nested: true)]
    private Collection $articles;

    /**
     * Category constructor.
     *
     * @param ArrayCollection<int, Article>|PersistentCollection<int, Article> $articles
     */
    public function __construct(int $id, string $title, Collection $articles)
    {
        $this->id = $id;
        $this->title = $title;
        $this->articles = $articles;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return ArrayCollection<int, Article>|PersistentCollection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }
}
