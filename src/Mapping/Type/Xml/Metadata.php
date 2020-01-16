<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Xml;

use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Xml;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;

final class Metadata extends AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    /**
     * @param \SimpleXMLElement $propertyMapping
     * @param \SimpleXMLElement $filteredMapping
     * @param RA                $config
     *
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function readConfiguration(object $propertyMapping, object $filteredMapping, RA $config): RA
    {
        $mappingType = $this->getType();
        if (true === isset($filteredMapping->$mappingType)) {
            /** @var \SimpleXMLElement $attributes */
            $attributes = $propertyMapping->attributes();
            $children = $filteredMapping->$mappingType->children(Xml::WDS_NAMESPACE_URI);
            $config->getRA($mappingType)->set(
                (string)($attributes['name'] ?: $attributes['field']),
                (new RA())
                    ->set(
                        DoctrineCrudApiMappingTypeInterface::METADATA_TYPE,
                        (string)($children->{DoctrineCrudApiMappingTypeInterface::METADATA_TYPE}) ?: null
                    )
                    ->set(
                        DoctrineCrudApiMappingTypeInterface::METADATA_CLASS,
                        (string)($children->{DoctrineCrudApiMappingTypeInterface::METADATA_CLASS}) ?: null
                    )
                    ->set(
                        DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD,
                        new RA(
                            (array)($children
                                ->{DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD}
                                ->{DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD_ARGUMENT}
                            ) ?: []
                        )
                    )
            );
        }
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
