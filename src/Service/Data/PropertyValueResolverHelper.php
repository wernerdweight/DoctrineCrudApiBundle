<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Inflector\Inflector;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Event\PreSetPropertyEvent;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Event\DoctrineCrudApiEventDispatcher;
use WernerDweight\RA\RA;

class PropertyValueResolverHelper
{
    /** @var DoctrineCrudApiEventDispatcher */
    private $eventDispatcher;

    /**
     * CreateHelper constructor.
     *
     * @param DoctrineCrudApiEventDispatcher $eventDispatcher
     */
    public function __construct(DoctrineCrudApiEventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param mixed       $value
     * @param string|null $type
     *
     * @return bool
     */
    public function isNewEntity($value, ?string $type): bool
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
            foreach ($resolvedValue as $collectionValue) {
                $item->{\Safe\sprintf('add%s', ucfirst(Inflector::singularize($field)))}($collectionValue);
            }
            return $item;
        }

        $item->{\Safe\sprintf('set%s', ucfirst($field))}($resolvedValue);
        return $item;
    }
}
