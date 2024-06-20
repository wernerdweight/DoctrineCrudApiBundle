<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

final class StringValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     */
    public function getPropertyValue($value, RA $configuration): ?string
    {
        if (ParameterEnum::EMPTY_VALUE === $value) {
            return null;
        }
        if ($value instanceof RA) {
            return $value->join(',');
        }
        return (string)$value;
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
