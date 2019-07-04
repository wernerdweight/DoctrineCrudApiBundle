<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Common\Collections\ArrayCollection;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\CurrentEntityResolver;
use WernerDweight\RA\RA;

class ModifyHelper
{
    /** @var CurrentEntityResolver */
    private $currentEntityResolver;

    /** @var ConfigurationManager */
    private $configurationManager;

    /** @var PropertyValueResolverHelper */
    private $propertyValueResolverHelper;

    /** @var MappingResolver */
    private $mappingResolver;

    /** @var DataManager */
    private $dataManager;

    /** @var RA */
    private $nestedItems;

    /**
     * CreateHelper constructor.
     *
     * @param CurrentEntityResolver       $currentEntityResolver
     * @param ConfigurationManager        $configurationManager
     * @param PropertyValueResolverHelper $propertyValueResolverHelper
     * @param MappingResolver             $mappingResolver
     * @param DataManager                 $dataManager
     */
    public function __construct(
        CurrentEntityResolver $currentEntityResolver,
        ConfigurationManager $configurationManager,
        PropertyValueResolverHelper $propertyValueResolverHelper,
        MappingResolver $mappingResolver,
        DataManager $dataManager
    ) {
        $this->currentEntityResolver = $currentEntityResolver;
        $this->configurationManager = $configurationManager;
        $this->propertyValueResolverHelper = $propertyValueResolverHelper;
        $this->mappingResolver = $mappingResolver;
        $this->dataManager = $dataManager;

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
        $nestedClassName = $this->mappingResolver
            ->getNestedCreatableClassName($field, $value, $metadata, $fieldMetadata);
        $nestedItem = $this->create($value, $nestedClassName);
        $this->nestedItems->push($nestedItem);
        return $nestedItem;
    }

    /**
     * @param ApiEntityInterface      $item
     * @param mixed                   $value
     * @param DoctrineCrudApiMetadata $metadata
     * @param string                  $field
     *
     * @return ApiEntityInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function updateExistingEntity(
        ApiEntityInterface $item,
        $value,
        DoctrineCrudApiMetadata $metadata,
        string $field
    ): ApiEntityInterface {
        $nestedEntity = $this->mappingResolver->getNestedUpdatable($item, $metadata, $field, $value);
        return $this->update($nestedEntity, $value);
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
    public function resolveValue(string $field, $value, DoctrineCrudApiMetadata $metadata)
    {
        /** @var RA|null $fieldMetadata */
        /** @var string $type */
        [$type, $fieldMetadata] = $this->mappingResolver->getFieldTypeAndMetadata($metadata, $field);
        if (null === $type || null === $fieldMetadata) {
            return $value;
        }
        if (true === $this->propertyValueResolverHelper->isNewEntity($value, $type)) {
            return $this->createNewEntity($field, $value, $metadata, $fieldMetadata);
        }
        if (true === $this->propertyValueResolverHelper->isUpdatableEntity($value, $type)) {
            /** @var ApiEntityInterface $nestedEntity */
            $nestedEntity = $this->mappingResolver->resolveValue($fieldMetadata, $value);
            return $this->updateExistingEntity($nestedEntity, $value, $metadata, $field);
        }
        if (true === $this->propertyValueResolverHelper->isCollection($value, $type)) {
            return new ArrayCollection($value->map(function ($collectionValue) use (
                $field,
                $metadata,
                $fieldMetadata
            ): ApiEntityInterface {
                if ($this->propertyValueResolverHelper->isNewCollectionItem($collectionValue)) {
                    return $this->createNewEntity($field, $collectionValue, $metadata, $fieldMetadata);
                }
                if ($this->propertyValueResolverHelper->isUpdatableCollectionItem($collectionValue)) {
                    $nestedMetadata = $this->mappingResolver->getNestedCollectionItemMetadata($fieldMetadata);
                    /** @var ApiEntityInterface $nestedEntity */
                    $nestedEntity = $this->mappingResolver->resolveValue($nestedMetadata, $collectionValue);
                    return $this->updateExistingEntity($nestedEntity, $collectionValue, $metadata, $field);
                }
                return $this->mappingResolver->resolveValue($fieldMetadata, $collectionValue);
            })->toArray());
        }
        return $this->mappingResolver->resolveValue($fieldMetadata, $value);
    }

    /**
     * @param ApiEntityInterface      $item
     * @param string                  $field
     * @param RA                      $fieldValues
     * @param DoctrineCrudApiMetadata $configuration
     *
     * @return ApiEntityInterface
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function setProperty(
        ApiEntityInterface $item,
        string $field,
        RA $fieldValues,
        DoctrineCrudApiMetadata $configuration
    ): ApiEntityInterface {
        if (true !== $fieldValues->hasKey($field)) {
            return $item;
        }
        $value = $this->propertyValueResolverHelper->getPreSetValue($item, $field, $fieldValues->get($field));
        $resolvedValue = $this->resolveValue($field, $value, $configuration);
        return $this->propertyValueResolverHelper->setResolvedValue($field, $resolvedValue, $item);
    }

    /**
     * @param RA          $values
     * @param string|null $itemClassName
     *
     * @return ApiEntityInterface
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function create(RA $values, ?string $itemClassName = null): ApiEntityInterface
    {
        if (null === $itemClassName) {
            $itemClassName = (string)$this->currentEntityResolver->getCurrentEntityFQCN();
        }
        $item = new $itemClassName();
        $metadata = $this->configurationManager->getConfigurationForEntityClass($itemClassName);
        $metadata->getCreatableFields()->walk(function (string $field) use ($item, $values, $metadata): void {
            $this->setProperty($item, $field, $values, $metadata);
        });

        return $item;
    }

    /**
     * @param ApiEntityInterface $item
     * @param RA                 $values
     *
     * @return ApiEntityInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function update(ApiEntityInterface $item, RA $values): ApiEntityInterface
    {
        $metadata = $this->configurationManager->getConfigurationForEntity($item);
        $metadata->getUpdatableFields()->walk(function (string $field) use ($item, $values, $metadata): void {
            $this->setProperty($item, $field, $values, $metadata);
        });

        return $item;
    }

    /**
     * @param string $primaryKey
     *
     * @return ApiEntityInterface
     */
    public function fetch(string $primaryKey): ApiEntityInterface
    {
        return $this->dataManager->getItem($primaryKey);
    }

    /**
     * @return RA
     */
    public function getNestedItems(): RA
    {
        return $this->nestedItems;
    }
}