<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

final class ArrayValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     *
     * @return mixed[]|null
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
            Type::OBJECT,
        ];
    }
}
