<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

class BooleanValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     * @param RA $configuration
     * @return bool|null
     */
    public function getPropertyValue($value, RA $configuration): ?bool
    {
        return ParameterEnum::TRUE_VALUE === $value;
    }

    /**
     * @return string[]
     */
    public function getPropertyTypes(): array
    {
        return [
            Type::BOOLEAN,
        ];
    }
}
