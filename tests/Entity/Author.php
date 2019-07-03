<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Entity;

use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;

final class Author implements ApiEntityInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
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
