<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Annotation;

use WernerDweight\DoctrineCrudApiBundle\Exception\AnnotationDriverException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;

final class Accessible implements DoctrineCrudApiMappingTypeInterface
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return DoctrineCrudApiMappingTypeInterface::ACCESSIBLE;
    }

    /**
     * @param object $propertyMapping
     * @param object $filteredMapping
     * @param RA     $config
     *
     * @return RA
     */
    public function readConfiguration(object $propertyMapping, object $filteredMapping, RA $config): RA
    {
        throw new AnnotationDriverException(AnnotationDriverException::NO_CONFIGURATION_NEEDED_FOR_ACCESSIBLE);
    }
}
