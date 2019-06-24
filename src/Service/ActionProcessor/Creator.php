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
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ConfigurationManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\DataManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ItemValidator;
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

    /**
     * Creator constructor.
     * @param ParameterResolver $parameterResolver
     * @param DataManager $dataManager
     * @param Formatter $formatter
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $entityManager
     * @param ItemValidator $itemValidator
     * @param CurrentEntityResolver $currentEntityResolver
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        DataManager $dataManager,
        Formatter $formatter,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager,
        ItemValidator $itemValidator,
        CurrentEntityResolver $currentEntityResolver,
        ConfigurationManager $configurationManager
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->dataManager = $dataManager;
        $this->formatter = $formatter;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->itemValidator = $itemValidator;
        $this->currentEntityResolver = $currentEntityResolver;
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param ApiEntityInterface $item
     * @param string $field
     * @param $value
     * @return mixed
     */
    private function getPreSetValue(ApiEntityInterface $item, string $field, $value)
    {
        /** @var PreSetPropertyEvent $event */
        $event = $this->eventDispatcher->dispatch(new PreSetPropertyEvent($item, $field, $value));
        return $event->getValue();
    }

    private function resolveValue(string $field, $value, DoctrineCrudApiMetadata $metadata)
    {
        $type = $metadata->getFieldType($field);
        if (null === $fieldMetadata || null === $type) {
            return $value;
        }

        $fieldMetadata = $metadata->getFieldMetadata($field);
        
    }

    private function create(): ApiEntityInterface
    {
        $itemClassName = $this->currentEntityResolver->getCurrentEntityFQCN();
        $item = new $itemClassName();

        // TODO: iterate over creatable fields and look for data in request (parameter resolver)
        $fieldValues = $this->parameterResolver->getRA(ParameterEnum::FIELDS);
        $configuration = $this->configurationManager->getConfigurationForEntityClass($itemClassName);
        $configuration->getCreatableFields()->walk(function ($value, string $field) use ($fieldValues, $configuration): void {
            dump($field, $value);
            if (true !== $fieldValues->hasKey($field)) {
                return;
            }
            $value = $this->getPreSetValue($item, $field, $value);
            $this->{\Safe\sprintf('set%s', ucfirst($field))}(
                $this->resolveValue($field, $value, $configuration)
            );
        });
        exit;

        return $item;
    }

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
