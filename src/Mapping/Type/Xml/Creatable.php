<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Xml;

use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;

final class Creatable extends AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    /** @var string */
    private const ATTRIBUTE_NESTED = 'nested';
    /** @var stirng[] */
    private const NESTED_TRUE_VALUES = ['true', '1'];

    /**
     * @param \SimpleXMLElement $propertyMapping
     * @param \SimpleXMLElement $filteredMapping
     * @param RA                $config
     *
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    protected function readExtraConfiguration(
        \SimpleXMLElement $propertyMapping,
        \SimpleXMLElement $filteredMapping,
        RA $config
    ): RA {
        $filteredAttributes = $filteredMapping->attributes();
        if (true === isset($filteredAttributes[self::ATTRIBUTE_NESTED]) &&
            true === in_array((string)$filteredAttributes[self::ATTRIBUTE_NESTED], self::NESTED_TRUE_VALUES, true)
        ) {
            /** @var \SimpleXMLElement $attributes */
            $attributes = $propertyMapping->attributes();
            $config
                ->getRA(DoctrineCrudApiMappingTypeInterface::CREATABLE_NESTED)
                ->push((string)($attributes['name'] ?: $attributes['field']));
        }
        return $config;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return DoctrineCrudApiMappingTypeInterface::CREATABLE;
    }
}
