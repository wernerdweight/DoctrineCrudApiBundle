<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Event\PostCreateEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PrePersistEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PreSetPropertyEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PreValidateEvent;
use WernerDweight\DoctrineCrudApiBundle\Exception\CreatorReturnableException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ConfigurationManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\FilteringHelper;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ItemValidator;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\MappingResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\CurrentEntityResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\Formatter;
use WernerDweight\RA\RA;

class Creator
{
    /** @var ParameterResolver */
    private $parameterResolver;

    /** @var Formatter */
    private $formatter;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ItemValidator */
    private $itemValidator;

    /** @var CurrentEntityResolver */
    private $currentEntityResolver;

    /** @var ConfigurationManager */
    private $configurationManager;

    /** @var MappingResolver */
    private $mappingResolver;

    /** @var RA */
    private $nestedItems;

    /**
     * Creator constructor.
     *
     * @param ParameterResolver        $parameterResolver
     * @param Formatter                $formatter
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface   $entityManager
     * @param ItemValidator            $itemValidator
     * @param CurrentEntityResolver    $currentEntityResolver
     * @param ConfigurationManager     $configurationManager
     * @param MappingResolver          $mappingResolver
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        Formatter $formatter,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager,
        ItemValidator $itemValidator,
        CurrentEntityResolver $currentEntityResolver,
        ConfigurationManager $configurationManager,
        MappingResolver $mappingResolver
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->formatter = $formatter;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->itemValidator = $itemValidator;
        $this->currentEntityResolver = $currentEntityResolver;
        $this->configurationManager = $configurationManager;
        $this->mappingResolver = $mappingResolver;

        $this->nestedItems = new RA();
    }

    /**
     * @param ApiEntityInterface $item
     * @param string             $field
     * @param $value
     *
     * @return mixed
     */
    private function getPreSetValue(ApiEntityInterface $item, string $field, $value)
    {
        /** @var PreSetPropertyEvent $event */
        $event = $this->eventDispatcher->dispatch(new PreSetPropertyEvent($item, $field, $value));
        return $event->getValue();
    }

    /**
     * @param $value
     * @param string|null $type
     *
     * @return bool
     */
    private function isNewEntity($value, ?string $type): bool
    {
        return DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY === $type &&
            $value instanceof RA &&
            true !== $value->hasKey(FilteringHelper::IDENTIFIER_FIELD_NAME);
    }

    /**
     * @param string $field
     * @param $value
     * @param DoctrineCrudApiMetadata $metadata
     * @param RA|null                 $fieldMetadata
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
        ?RA $fieldMetadata
    ): ApiEntityInterface {
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
        $nestedClassName = $fieldMetadata->getString(DoctrineCrudApiMappingTypeInterface::METADATA_CLASS);
        $nestedItem = $this->create($nestedClassName, $value);
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
        if (true === $this->isNewEntity($value, $type)) {
            return $this->createNewEntity($field, $value, $metadata, $fieldMetadata);
        }
        if (DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_COLLECTION === $type && $value instanceof RA) {
            return new ArrayCollection($value->map(function ($collectionValue) use (
                $field,
                $metadata,
                $fieldMetadata
            ): ApiEntityInterface {
                if ($collectionValue instanceof RA && true !== $collectionValue->hasKey(
                    FilteringHelper::IDENTIFIER_FIELD_NAME
                )) {
                    return $this->createNewEntity($field, $collectionValue, $metadata, $fieldMetadata);
                }
                return $this->mappingResolver->resolveValue($fieldMetadata, $collectionValue);
            })->toArray());
        }
        return $this->mappingResolver->resolveValue($fieldMetadata, $value);
    }

    /**
     * @param string $itemClassName
     * @param RA     $fieldValues
     *
     * @return ApiEntityInterface
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function create(string $itemClassName, RA $fieldValues): ApiEntityInterface
    {
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
            $value = $this->getPreSetValue($item, $field, $fieldValues->get($field));
            $resolvedValue = $this->resolveValue($field, $value, $configuration);
            if ($resolvedValue instanceof ArrayCollection) {
                foreach ($resolvedValue as $collectionValue) {
                    $item->{\Safe\sprintf('add%s', ucfirst(Inflector::singularize($field)))}($collectionValue);
                }
                return;
            }
            $item->{\Safe\sprintf('set%s', ucfirst($field))}($resolvedValue);
        });

        return $item;
    }

    /**
     * @return RA
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function createItem(): RA
    {
        $this->parameterResolver->resolveCreate();
        $itemClassName = (string)$this->currentEntityResolver->getCurrentEntityFQCN();
        $fieldValues = $this->parameterResolver->getRA(ParameterEnum::FIELDS);
        $item = $this->create($itemClassName, $fieldValues);

        $this->eventDispatcher->dispatch(new PreValidateEvent($item));
        $this->itemValidator->validate($item);

        $this->eventDispatcher->dispatch(new PrePersistEvent($item));
        $this->entityManager->persist($item);
        $this->nestedItems->walk(function (ApiEntityInterface $nestedItem): void {
            $this->entityManager->persist($nestedItem);
        });
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostCreateEvent($item));
        return $this->formatter->format(
            $item,
            $this->parameterResolver->getRAOrNull(ParameterEnum::RESPONSE_STRUCTURE),
            $this->parameterResolver->getEntityPrefix()
        );
    }
}
