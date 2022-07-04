<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use Safe\DateTimeImmutable;
use WernerDweight\DoctrineCrudApiBundle\Exception\DateTimeValueResolverException;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class DateTimeImmutableValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     *
     * @return DateTimeImmutable|null
     */
    public function getPropertyValue($value, RA $configuration): ?\DateTimeImmutable
    {
        if (true === empty($value) || ParameterEnum::NULL_VALUE === $value) {
            return null;
        }
        $stringyValue = new Stringy($value);
        // remove localized timezone (some browsers use localized names)
        $stringyValue = $stringyValue->eregReplace('^([^\(]*)\s(.*)$', '\\1');
        $isValidDate = $stringyValue->pregMatch('/^(\d{4}-(0?\d|1[0-2])-([0-2]?\d|3[01])|([0-2]?\d|3[01])\.(0?\d|1[0-2])\.\d{4}|(0?\d|1[0-2])\/([0-2]?\d|3[01])\/\d{4})/');
        if (false === $isValidDate) {
            throw new DateTimeValueResolverException(DateTimeValueResolverException::INVALID_VALUE);
        }
        $value = (string)($stringyValue);
        return new DateTimeImmutable(
            $value
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
