<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

final class IntegerValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     * @param RA    $configuration
     *
     * @return int|null
     */
    public function getPropertyValue($value, RA $configuration): ?int
    {
        return ParameterEnum::EMPTY_VALUE !== $value ? (int)$value : null;
    }

    /**
     * @return string[]
     */
    public function getPropertyTypes(): array
    {
        return [
            Type::INTEGER,
            Type::BIGINT,
            Type::SMALLINT,
        ];
    }
}
