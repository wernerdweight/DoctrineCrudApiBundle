<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Xml;

use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;

abstract class AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    /**
     * @param \SimpleXMLElement $propertyMapping
     * @param \SimpleXMLElement $filteredMapping
     * @param RA                $config
     *
     * @return RA
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function readExtraConfiguration(
        \SimpleXMLElement $propertyMapping,
        \SimpleXMLElement $filteredMapping,
        RA $config
    ): RA {
        return $config;
    }

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
            $config->getRA($mappingType)->push((string)($attributes['name'] ?: $attributes['field']));
            $config = $this->readExtraConfiguration($propertyMapping, $filteredMapping->$mappingType, $config);
        }
        return $config;
    }
}
