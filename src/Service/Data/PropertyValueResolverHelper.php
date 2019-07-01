<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Event\PreSetPropertyEvent;
use WernerDweight\DoctrineCrudApiBundle\Exception\CreatorReturnableException;
use WernerDweight\DoctrineCrudApiBundle\Exception\InvalidRequestException;
use WernerDweight\DoctrineCrudApiBundle\Exception\UpdaterReturnableException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Event\DoctrineCrudApiEventDispatcher;
use WernerDweight\RA\RA;

class PropertyValueResolverHelper
{
    /** @var string */
    private const ROUTE_KEY = '_route';
    /** @var string */
    private const UPDATE_ROUTE_NAME = 'wds_doctrine_crud_api_update';

    /** @var DoctrineCrudApiEventDispatcher */
    private $eventDispatcher;

    /** @var Request */
    private $request;

    /**
     * PropertyValueResolverHelper constructor.
     *
     * @param DoctrineCrudApiEventDispatcher $eventDispatcher
     * @param RequestStack                   $requestStack
     */
    public function __construct(DoctrineCrudApiEventDispatcher $eventDispatcher, RequestStack $requestStack)
    {
        $this->eventDispatcher = $eventDispatcher;

        $request = $requestStack->getCurrentRequest();
        if (null === $request) {
            throw new InvalidRequestException(InvalidRequestException::EXCEPTION_NO_REQUEST);
        }
        $this->request = $request;
    }

    /**
     * @param mixed  $value
     * @param string $type
     *
     * @return bool
     */
    public function isNewEntity($value, string $type): bool
    {
        return DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY === $type &&
            $value instanceof RA &&
            true !== $value->hasKey(FilteringHelper::IDENTIFIER_FIELD_NAME);
    }

    /**
     * @param mixed $collectionValue
     *
     * @return bool
     */
    public function isNewCollectionItem($collectionValue): bool
    {
        return $collectionValue instanceof RA &&
            true !== $collectionValue->hasKey(FilteringHelper::IDENTIFIER_FIELD_NAME);
    }

    /**
     * @param mixed  $value
     * @param string $type
     *
     * @return bool
     */
    public function isUpdatableEntity($value, string $type): bool
    {
        return self::UPDATE_ROUTE_NAME === $this->request->attributes->get(self::ROUTE_KEY) &&
            DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY === $type &&
            $value instanceof RA &&
            true === $value->hasKey(FilteringHelper::IDENTIFIER_FIELD_NAME) &&
            1 !== $value->length();     // more data than ID present
    }

    /**
     * @param mixed $collectionValue
     *
     * @return bool
     */
    public function isUpdatableCollectionItem($collectionValue): bool
    {
        return self::UPDATE_ROUTE_NAME === $this->request->attributes->get(self::ROUTE_KEY) &&
            $collectionValue instanceof RA &&
            true === $collectionValue->hasKey(FilteringHelper::IDENTIFIER_FIELD_NAME) &&
            1 !== $collectionValue->length();     // more data than ID present
    }

    /**
     * @param mixed  $value
     * @param string $type
     *
     * @return bool
     */
    public function isCollection($value, string $type): bool
    {
        return DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_COLLECTION === $type && $value instanceof RA;
    }

    /**
     * @param ApiEntityInterface $item
     * @param string             $field
     * @param mixed              $value
     *
     * @return mixed
     */
    public function getPreSetValue(ApiEntityInterface $item, string $field, $value)
    {
        /** @var PreSetPropertyEvent $event */
        $event = $this->eventDispatcher->dispatchPreSetProperty($item, $field, $value);
        return $event->getValue();
    }

    /**
     * @param string             $field
     * @param mixed              $resolvedValue
     * @param ApiEntityInterface $item
     *
     * @return ApiEntityInterface
     *
     * @throws \Safe\Exceptions\StringsException
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function setResolvedValue(string $field, $resolvedValue, ApiEntityInterface $item): ApiEntityInterface
    {
        if ($resolvedValue instanceof ArrayCollection) {
            $singularPropertyName = ucfirst(Inflector::singularize($field));
            /** @var Collection $currentValue */
            $currentValue = $item->{\Safe\sprintf('get%s', ucfirst($field))}();
            foreach ($currentValue as $collectionValue) {
                if (true !== $resolvedValue->contains($collectionValue)) {
                    $item->{\Safe\sprintf('remove%s', $singularPropertyName)}($collectionValue);
                }
            }
            foreach ($resolvedValue as $collectionValue) {
                if (true !== $currentValue->contains($collectionValue)) {
                    $item->{\Safe\sprintf('add%s', $singularPropertyName)}($collectionValue);
                }
            }
            return $item;
        }

        $item->{\Safe\sprintf('set%s', ucfirst($field))}($resolvedValue);
        return $item;
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
    public function getNestedClassName(
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
