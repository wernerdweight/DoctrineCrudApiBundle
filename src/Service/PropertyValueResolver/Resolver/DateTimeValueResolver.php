<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\DBAL\Types\Type;
use Safe\DateTime;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class DateTimeValueResolver implements PropertyValueResolverInterface
{
    /**
     * @param mixed $value
     *
     * @return DateTime|null
     *
     * @throws \Exception
     */
    public function getPropertyValue($value, RA $configuration): ?\DateTime
    {
        if (true === empty($value) || ParameterEnum::NULL_VALUE === $value) {
            return null;
        }
        $stringyValue = new Stringy($value);
        // remove localized timezone (some browsers use localized names)
        $stringyValue = $stringyValue->eregReplace('^([^\(]*)\s(.*)$', '\\1');
        $isValidDate = $stringyValue->pregMatch('/^(\d{4}-([0]\d|1[0-2])-([0-2]\d|3[01]))|(([0-2]\d|3[01]).([0]\d|1[0-2]).\d{4})/');
        if (false === $isValidDate) {
            return null;
        }
        $value = (string)($stringyValue);
        return new DateTime(
            $value
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
