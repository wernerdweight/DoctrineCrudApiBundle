<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Common\Collections\ArrayCollection;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\CurrentEntityResolver;
use WernerDweight\RA\RA;

class CreateHelper
{
    /** @var CurrentEntityResolver */
    private $currentEntityResolver;

    /** @var ConfigurationManager */
    private $configurationManager;

    /** @var PropertyValueResolverHelper */
    private $propertyValueResolverHelper;

    /** @var MappingResolver */
    private $mappingResolver;

    /** @var RA */
    private $nestedItems;

    /**
     * CreateHelper constructor.
     *
     * @param CurrentEntityResolver       $currentEntityResolver
     * @param ConfigurationManager        $configurationManager
     * @param PropertyValueResolverHelper $propertyValueResolverHelper
     * @param MappingResolver             $mappingResolver
     */
    public function __construct(
        CurrentEntityResolver $currentEntityResolver,
        ConfigurationManager $configurationManager,
        PropertyValueResolverHelper $propertyValueResolverHelper,
        MappingResolver $mappingResolver
    ) {
        $this->currentEntityResolver = $currentEntityResolver;
        $this->configurationManager = $configurationManager;
        $this->propertyValueResolverHelper = $propertyValueResolverHelper;
        $this->mappingResolver = $mappingResolver;

        $this->nestedItems = new RA();
    }

    /**
     * @param string                  $field
     * @param mixed                   $value
     * @param DoctrineCrudApiMetadata $metadata
     * @param RA                      $fieldMetadata
     *
     * @return ApiEntityInterface
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function createNewEntity(
        string $field,
        $value,
        DoctrineCrudApiMetadata $metadata,
        RA $fieldMetadata
    ): ApiEntityInterface {
        $nestedClassName = $this->propertyValueResolverHelper
            ->getNestedClassName($field, $value, $metadata, $fieldMetadata);
        $nestedItem = $this->create($value, $nestedClassName);
        $this->nestedItems->push($nestedItem);
        return $nestedItem;
    }

    /**
     * @param string                  $field
     * @param mixed                   $value
     * @param DoctrineCrudApiMetadata $metadata
     *
     * @return mixed
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function resolveValue(string $field, $value, DoctrineCrudApiMetadata $metadata)
    {
        $type = $metadata->getFieldType($field);
        $fieldMetadata = $metadata->getFieldMetadata($field);
        if (null === $type) {
            $type = $metadata->getInternalFieldType($field);
            $fieldMetadata = (new RA())->set(DoctrineCrudApiMappingTypeInterface::METADATA_TYPE, $type);
            if (null === $type) {
                return $value;
            }
        }
        if (null === $fieldMetadata) {
            return $value;
        }
        if (true === $this->propertyValueResolverHelper->isNewEntity($value, $type)) {
            return $this->createNewEntity($field, $value, $metadata, $fieldMetadata);
        }
        if (DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_COLLECTION === $type && $value instanceof RA) {
            return new ArrayCollection($value->map(function ($collectionValue) use (
                $field,
                $metadata,
                $fieldMetadata
            ): ApiEntityInterface {
                if ($this->propertyValueResolverHelper->isNewCollectionItem($collectionValue)) {
                    return $this->createNewEntity($field, $collectionValue, $metadata, $fieldMetadata);
                }
                return $this->mappingResolver->resolveValue($fieldMetadata, $collectionValue);
            })->toArray());
        }
        return $this->mappingResolver->resolveValue($fieldMetadata, $value);
    }

    /**
     * @param RA          $fieldValues
     * @param string|null $itemClassName
     *
     * @return ApiEntityInterface
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function create(RA $fieldValues, ?string $itemClassName = null): ApiEntityInterface
    {
        if (null === $itemClassName) {
            $itemClassName = (string)$this->currentEntityResolver->getCurrentEntityFQCN();
        }
        $item = new $itemClassName();
        $configuration = $this->configurationManager->getConfigurationForEntityClass($itemClassName);
        $configuration->getCreatableFields()->walk(function (string $field) use (
            $item,
            $fieldValues,
            $configuration
        ): void {
            if (true !== $fieldValues->hasKey($field)) {
                return;
            }
            $value = $this->propertyValueResolverHelper->getPreSetValue($item, $field, $fieldValues->get($field));
            $resolvedValue = $this->resolveValue($field, $value, $configuration);
            $this->propertyValueResolverHelper->setResolvedValue($field, $resolvedValue, $item);
        });

        return $item;
    }

    /**
     * @return RA
     */
    public function getNestedItems(): RA
    {
        return $this->nestedItems;
    }
}
