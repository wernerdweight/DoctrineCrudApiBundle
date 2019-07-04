<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation as WDS;

/**
 * Category.
 *
 * @ORM\Table(name="test_category")
 * @ORM\Entity()
 * @WDS\Accessible()
 */
final class Category implements ApiEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     * @WDS\Listable(default=true)
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
     * @var ArrayCollection|PersistentCollection
     *
     * @ORM\OneToMany(targetEntity="Article", mappedBy="category")
     * @WDS\Listable()
     * @WDS\Creatable(nested=true)
     * @WDS\Updatable(nested=true)
     */
    private $articles;

    /**
     * Category constructor.
     *
     * @param int                                  $id
     * @param string                               $title
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
