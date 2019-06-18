<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Annotation;

use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation\Metadata as MetadataAnnotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Xml;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class Metadata extends AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    /**
     * @param Stringy $propertyName
     * @param MetadataAnnotation $annotation
     * @param RA $config
     * @return RA
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function readConfiguration(object $propertyName, object $annotation, RA $config): RA
    {
        $mappingType = $this->getType();
        $config->getRA($mappingType)->set(
            (string)$propertyName,
            (new RA())
                ->set(DoctrineCrudApiMappingTypeInterface::METADATA_TYPE, $annotation->type)
                ->set(DoctrineCrudApiMappingTypeInterface::METADATA_CLASS, $annotation->class)
                ->set(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD, $annotation->payload)
        );
        return $config;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return DoctrineCrudApiMappingTypeInterface::METADATA;
    }
}
