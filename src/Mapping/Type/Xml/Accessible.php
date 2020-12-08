<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Xml;

use WernerDweight\DoctrineCrudApiBundle\Exception\XmlDriverException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;

final class Accessible implements DoctrineCrudApiMappingTypeInterface
{
    public function getType(): string
    {
        return DoctrineCrudApiMappingTypeInterface::ACCESSIBLE;
    }

    public function readConfiguration(object $propertyMapping, object $filteredMapping, RA $config): RA
    {
        throw new XmlDriverException(XmlDriverException::NO_CONFIGURATION_NEEDED_FOR_ACCESSIBLE);
    }
}
