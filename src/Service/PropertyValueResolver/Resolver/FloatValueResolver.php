<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Types;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

final class FloatValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     */
    public function getPropertyValue($value, RA $configuration): ?float
    {
        return ParameterEnum::EMPTY_VALUE !== $value ? (float)$value : null;
    }

    /**
     * @return string[]
     */
    public function getPropertyTypes(): array
    {
        return [
            Types::FLOAT,
            Types::DECIMAL,
        ];
    }
}
