<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use WernerDweight\RA\RA;

interface PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     * @param RA $configuration
     * @return mixed
     */
    public function getPropertyValue($value, RA $configuration);

    /**
     * @return (int|string)[]
     */
    public function getPropertyTypes(): array;
}
