<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor;

use Doctrine\ORM\EntityManagerInterface;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\DataManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ItemValidator;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ModifyHelper;
use WernerDweight\DoctrineCrudApiBundle\Service\Event\DoctrineCrudApiEventDispatcher;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\Formatter;
use WernerDweight\RA\RA;

class Updater
{
    /** @var ParameterResolver */
    private $parameterResolver;

    /** @var Formatter */
    private $formatter;

    /** @var DoctrineCrudApiEventDispatcher */
    private $eventDispatcher;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ItemValidator */
    private $itemValidator;

    /** @var ModifyHelper */
    private $modifyHelper;

    /** @var DataManager */
    private $dataManager;

    /**
     * Updater constructor.
     *
     * @param ParameterResolver              $parameterResolver
     * @param Formatter                      $formatter
     * @param DoctrineCrudApiEventDispatcher $eventDispatcher
     * @param EntityManagerInterface         $entityManager
     * @param ItemValidator                  $itemValidator
     * @param ModifyHelper                   $modifyHelper
     * @param DataManager                    $dataManager
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        Formatter $formatter,
        DoctrineCrudApiEventDispatcher $eventDispatcher,
        EntityManagerInterface $entityManager,
        ItemValidator $itemValidator,
        ModifyHelper $modifyHelper,
        DataManager $dataManager
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->formatter = $formatter;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->itemValidator = $itemValidator;
        $this->modifyHelper = $modifyHelper;
        $this->dataManager = $dataManager;
    }

    /**
     * @return ApiEntityInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function fetch(): ApiEntityInterface
    {
        return $this->dataManager->getItem(
            $this->parameterResolver->getString(ParameterEnum::PRIMARY_KEY)
        );
    }

    /**
     * @return RA
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function updateItem(): RA
    {
        $this->parameterResolver->resolveUpdate();
        $fieldValues = $this->parameterResolver->getRA(ParameterEnum::FIELDS);
        $item = $this->modifyHelper->update($this->fetch(), $fieldValues);

        $this->eventDispatcher->dispatchPreValidate($item);
        $this->itemValidator->validate($item);

        $this->eventDispatcher->dispatchPreUpdate($item);
        $this->modifyHelper->getNestedItems()->walk(function (ApiEntityInterface $nestedItem): void {
            $this->entityManager->persist($nestedItem);
        });
        $this->entityManager->flush();

        $this->eventDispatcher->dispatchPostUpdate($item);
        return $this->formatter->format(
            $item,
            $this->parameterResolver->getRAOrNull(ParameterEnum::RESPONSE_STRUCTURE),
            $this->parameterResolver->getEntityPrefix()
        );
    }
}
