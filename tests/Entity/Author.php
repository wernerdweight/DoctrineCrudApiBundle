<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation as WDS;

#[ORM\Table(name: 'test_author')]
#[ORM\Entity()]
#[WDS\Accessible()]
final class Author implements ApiEntityInterface
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[WDS\Listable(default: true)]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', nullable: false)]
    #[WDS\Listable(default: true)]
    #[WDS\Creatable()]
    #[WDS\Updatable()]
    private string $name;

    #[ORM\Column(name: 'email', type: 'string', nullable: false)]
    #[WDS\Listable()]
    #[WDS\Creatable()]
    #[WDS\Updatable()]
    private string $email;

    public function __construct(int $id, string $name, string $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
