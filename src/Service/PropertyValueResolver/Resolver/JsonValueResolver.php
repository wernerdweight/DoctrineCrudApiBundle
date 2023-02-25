<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Types;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

final class JsonValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     *
     * @return mixed[]|null
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
            Types::JSON,
        ];
    }
}
