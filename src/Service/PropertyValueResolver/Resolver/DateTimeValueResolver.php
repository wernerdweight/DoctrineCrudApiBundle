<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class DateTimeValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     * @param RA    $configuration
     *
     * @return \DateTime|null
     */
    public function getPropertyValue($value, RA $configuration): ?\DateTime
    {
        if (true === empty($value) || ParameterEnum::NULL_VALUE === $value) {
            return null;
        }
        return new \DateTime(
            // remove localized timezone (some browsers use localized names)
            (string)((new Stringy($value))->eregReplace('^([^\(]*)\s(.*$', '\\1'))
        );
    }

    /**
     * @return string[]
     */
    public function getPropertyTypes(): array
    {
        return [
            Type::DATE,
            Type::DATETIME,
            Type::DATETIMETZ,
            Type::DATEINTERVAL,
            Type::TIME,
        ];
    }
}
