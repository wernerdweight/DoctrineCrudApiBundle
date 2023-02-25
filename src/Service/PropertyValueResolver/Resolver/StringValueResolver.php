<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Types;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

final class StringValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
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
            Types::STRING,
            Types::TEXT,
            Types::BINARY,
            Types::BLOB,
            Types::GUID,
        ];
    }
}
