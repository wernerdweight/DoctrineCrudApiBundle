<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use WernerDweight\DoctrineCrudApiBundle\Exception\MappingResolverException;
use WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\PropertyValueResolverFactory;
use WernerDweight\RA\RA;

class MappingResolver
{
    /** @var PropertyValueResolverFactory */
    private $propertyValueResolverFactory;

    /**
     * MappingResolver constructor.
     *
     * @param PropertyValueResolverFactory $propertyValueResolverFactory
     */
    public function __construct(PropertyValueResolverFactory $propertyValueResolverFactory)
    {
        $this->propertyValueResolverFactory = $propertyValueResolverFactory;
    }

    /**
     * @param RA    $configuration
     * @param mixed $value
     *
     * @return mixed
     */
    public function resolveValue(RA $configuration, $value)
    {
        if (true !== $configuration->hasKey(FilteringHelper::DOCTRINE_ASSOCIATION_TYPE)) {
            throw new MappingResolverException(MappingResolverException::EXCEPTION_MISSING_MAPPING_TYPE);
        }
        $type = (string)$configuration->get(FilteringHelper::DOCTRINE_ASSOCIATION_TYPE);
        return $this->propertyValueResolverFactory->get($type)->getPropertyValue($value, $configuration);
    }
}
