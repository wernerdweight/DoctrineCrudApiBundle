<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\Stringy\Stringy;

class Printer
{
    /**
     * @throws \Safe\Exceptions\PcreException
     */
    private function printObject(object $value): string
    {
        if ($value instanceof \DateTime) {
            $formatedDateTime = new Stringy($value->format('c'));
            return (string)($formatedDateTime->pregReplace('/:([\d]{2})$/', '$1'));
        }
        if (true === method_exists($value, '__toString')) {
            return (string)$value;
        }
        return ParameterEnum::OBJECT_VALUE;
    }

    /**
     * @param mixed $value
     *
     * @throws \Safe\Exceptions\PcreException
     */
    public function print($value): string
    {
        if (null === $value) {
            return ParameterEnum::NULL_VALUE;
        }
        if (true === $value) {
            return ParameterEnum::TRUE_VALUE;
        }
        if (false === $value) {
            return ParameterEnum::FALSE_VALUE;
        }
        if (true === is_numeric($value)) {
            return (string)$value;
        }
        if (true === is_array($value)) {
            return ParameterEnum::ARRAY_VALUE;
        }
        if (true === is_object($value)) {
            return $this->printObject($value);
        }
        /** @var string|mixed $value */
        $value = $value;
        if (true === is_string($value)) {
            return $value;
        }
        return ParameterEnum::UNDEFINED_VALUE;
    }
}
