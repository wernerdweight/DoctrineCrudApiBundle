<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

class ArrayValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     * @param RA $configuration
     * @return array|null
     */
    public function getPropertyValue($value, RA $configuration): ?array
    {
        return (true !== empty($value) && ParameterEnum::NULL_VALUE !== $value) ? (array)$value : null;
    }

    /**
     * @return string[]
     */
    public function getPropertyTypes(): array
    {
        return [
            Type::TARRAY,
            Type::SIMPLE_ARRAY,
            Type::JSON_ARRAY,
            Type::JSON,
            Type::OBJECT,
        ];
    }
}
