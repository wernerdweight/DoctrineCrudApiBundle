<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

class StringValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     * @param RA $configuration
     * @return string|null
     */
    public function getPropertyValue($value, RA $configuration): ?string
    {
        return ParameterEnum::EMPTY_VALUE !== $value ? (string)$value : null;
    }

    /**
     * @return string[]
     */
    public function getPropertyTypes(): array
    {
        return [
            Type::STRING,
            Type::TEXT,
            Type::BINARY,
            Type::BLOB,
            Type::GUID,
        ];
    }
}
