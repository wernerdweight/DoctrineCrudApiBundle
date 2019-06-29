<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Event\PostCreateEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PrePersistEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PreSetPropertyEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PreValidateEvent;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ConfigurationManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\DataManager;
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

    /** @var DataManager */
    private $dataManager;

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

    /**
     * Creator constructor.
     *
     * @param ParameterResolver        $parameterResolver
     * @param DataManager              $dataManager
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
        DataManager $dataManager,
        Formatter $formatter,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager,
        ItemValidator $itemValidator,
        CurrentEntityResolver $currentEntityResolver,
        ConfigurationManager $configurationManager,
        MappingResolver $mappingResolver
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->dataManager = $dataManager;
        $this->formatter = $formatter;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->itemValidator = $itemValidator;
        $this->currentEntityResolver = $currentEntityResolver;
        $this->configurationManager = $configurationManager;
        $this->mappingResolver = $mappingResolver;
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
        return $this->mappingResolver->resolveValue($fieldMetadata, $value);
    }

    /**
     * @return ApiEntityInterface
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function create(): ApiEntityInterface
    {
        $itemClassName = (string)$this->currentEntityResolver->getCurrentEntityFQCN();
        $item = new $itemClassName();

        $fieldValues = $this->parameterResolver->getRA(ParameterEnum::FIELDS);
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
            $item->{\Safe\sprintf('set%s', ucfirst($field))}(
                $this->resolveValue($field, $value, $configuration)
            );
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
        $item = $this->create();

        $this->eventDispatcher->dispatch(new PreValidateEvent($item));
        $this->itemValidator->validate($item);

        $this->eventDispatcher->dispatch(new PrePersistEvent($item));
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostCreateEvent($item));
        return $item;
    }
}
