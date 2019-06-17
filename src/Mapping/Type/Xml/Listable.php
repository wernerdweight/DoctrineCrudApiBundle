<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Xml;

use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;

final class Listable extends AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    /** @var string */
    private const ATTRIBUTE_DEFAULT = 'default';
    /** @var stirng[] */
    private const DEFAULT_TRUE_VALUES = ['true', '1'];

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
        if (true === isset($filteredAttributes[self::ATTRIBUTE_DEFAULT]) &&
            true === in_array((string)$filteredAttributes[self::ATTRIBUTE_DEFAULT], self::DEFAULT_TRUE_VALUES, true)
        ) {
            $attributes = $propertyMapping->attributes();
            $config
                ->getRA(DoctrineCrudApiMappingTypeInterface::DEFAULT_LISTABLE)
                ->push((string)($attributes['name'] ?: $attributes['field']));
        }
        return $config;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return DoctrineCrudApiMappingTypeInterface::LISTABLE;
    }
}
