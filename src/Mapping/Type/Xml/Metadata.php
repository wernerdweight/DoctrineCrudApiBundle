<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Xml;

use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Xml;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;

final class Metadata extends AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    /** @var string */
    private const CHILD_TYPE = 'type';
    /** @var string */
    private const CHILD_CLASS = 'class';

    /**
     * @param \SimpleXMLElement $propertyMapping
     * @param \SimpleXMLElement $filteredMapping
     * @param RA $config
     * @return RA
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function readConfiguration(object $propertyMapping, object $filteredMapping, RA $config): RA
    {
        $mappingType = $this->getType();
        if (true === isset($filteredMapping->$mappingType)) {
            $attributes = $propertyMapping->attributes();
            $children = $filteredMapping->$mappingType->children(Xml::WDS_NAMESPACE_URI);
            $config->getRA($mappingType)->set(
                (string)($attributes['name'] ?: $attributes['field']),
                (new RA())
                    ->set(self::CHILD_TYPE, (string)($children->{self::CHILD_TYPE}) ?: null)
                    ->set(self::CHILD_CLASS, (string)($children->{self::CHILD_CLASS}) ?: null)
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
