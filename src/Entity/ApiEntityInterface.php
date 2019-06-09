<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Entity;

interface ApiEntityInterface
{
    /**
     * @return string|int
     */
    public function getId();
}
