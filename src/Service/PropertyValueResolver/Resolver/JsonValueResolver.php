<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

final class JsonValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     * @param RA    $configuration
     *
     * @return array|null
     */
    public function getPropertyValue($value, RA $configuration): ?array
    {
        return (true !== empty($value) && ParameterEnum::NULL_VALUE !== $value)
            ? (is_array($value) ? $value : \Safe\json_decode($value, true))
            : null;
    }

    /**
     * @return string[]
     */
    public function getPropertyTypes(): array
    {
        return [
            Type::JSON_ARRAY,
            Type::JSON,
        ];
    }
}
