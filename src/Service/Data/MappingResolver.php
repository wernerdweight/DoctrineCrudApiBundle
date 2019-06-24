<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use WernerDweight\DoctrineCrudApiBundle\Exception\MappingResolverException;
use WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\PropertyValueResolverFactory;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class MappingResolver
{
    /** @var PropertyValueResolverFactory */
    private $propertyValueResolverFactory;

    /**
     * MappingResolver constructor.
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
