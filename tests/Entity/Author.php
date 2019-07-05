<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation as WDS;

/**
 * Author.
 *
 * @ORM\Table(name="test_author")
 * @ORM\Entity()
 * @WDS\Accessible()
 */
final class Author implements ApiEntityInterface
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
     * @ORM\Column(name="name", type="string", nullable=false)
     * @WDS\Listable(default=true)
     * @WDS\Creatable()
     * @WDS\Updatable()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=false)
     * @WDS\Listable()
     * @WDS\Creatable()
     * @WDS\Updatable()
     */
    private $email;

    /**
     * Author constructor.
     *
     * @param int    $id
     * @param string $name
     * @param string $email
     */
    public function __construct(int $id, string $name, string $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
