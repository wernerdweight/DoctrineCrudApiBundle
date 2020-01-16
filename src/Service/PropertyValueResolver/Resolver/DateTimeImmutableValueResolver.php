<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use Safe\DateTimeImmutable;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class DateTimeImmutableValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     * @param RA    $configuration
     *
     * @return DateTimeImmutable|null
     */
    public function getPropertyValue($value, RA $configuration): ?\DateTimeImmutable
    {
        if (true === empty($value) || ParameterEnum::NULL_VALUE === $value) {
            return null;
        }
        return new DateTimeImmutable(
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
            Type::DATE_IMMUTABLE,
            Type::DATETIME_IMMUTABLE,
            Type::DATETIMETZ_IMMUTABLE,
            Type::TIME_IMMUTABLE,
        ];
    }
}
