<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\CreatorReturnableException;
use WernerDweight\DoctrineCrudApiBundle\Exception\MappingResolverException;
use WernerDweight\DoctrineCrudApiBundle\Exception\UpdaterReturnableException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
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

    /**
     * @param ApiEntityInterface      $item
     * @param DoctrineCrudApiMetadata $metadata
     * @param string                  $field
     * @param mixed                   $value
     *
     * @return ApiEntityInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getNestedUpdatable(
        ApiEntityInterface $item,
        DoctrineCrudApiMetadata $metadata,
        string $field,
        $value
    ): ApiEntityInterface {
        if (true !== $metadata->getUpdatableNested()->contains($field)) {
            throw new UpdaterReturnableException(
                UpdaterReturnableException::INVALID_NESTING,
                [
                    'root' => $metadata->getShortName(),
                    'nested' => $field,
                    'value' => $value instanceof RA ? $value->toArray(RA::RECURSIVE) : $value,
                ]
            );
        }
        return $item;
    }

    /**
     * @param string                  $field
     * @param mixed                   $value
     * @param DoctrineCrudApiMetadata $metadata
     * @param RA                      $fieldMetadata
     *
     * @return string
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getNestedCreatableClassName(
        string $field,
        $value,
        DoctrineCrudApiMetadata $metadata,
        RA $fieldMetadata
    ): string {
        if (true !== $metadata->getCreatableNested()->contains($field)) {
            throw new CreatorReturnableException(
                CreatorReturnableException::INVALID_NESTING,
                [
                    'root' => $metadata->getShortName(),
                    'nested' => $field,
                    'value' => $value instanceof RA ? $value->toArray(RA::RECURSIVE) : $value,
                ]
            );
        }
        return $fieldMetadata->getString(DoctrineCrudApiMappingTypeInterface::METADATA_CLASS);
    }

    /**
     * @param RA $fieldMetadata
     *
     * @return RA
     */
    public function getNestedCollectionItemMetadata(RA $fieldMetadata): RA
    {
        return (clone $fieldMetadata)->set(
            DoctrineCrudApiMappingTypeInterface::METADATA_TYPE,
            DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY
        );
    }

    /**
     * @param DoctrineCrudApiMetadata $metadata
     * @param string                  $field
     *
     * @return array
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFieldTypeAndMetadata(DoctrineCrudApiMetadata $metadata, string $field): array
    {
        $type = $metadata->getFieldType($field);
        $fieldMetadata = $metadata->getFieldMetadata($field);
        if (null === $type) {
            $type = $metadata->getInternalFieldType($field);
            $fieldMetadata = (new RA())->set(DoctrineCrudApiMappingTypeInterface::METADATA_TYPE, $type);
        }
        return [$type, $fieldMetadata];
    }
}
