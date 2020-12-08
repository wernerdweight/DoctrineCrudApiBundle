<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor;

use Doctrine\ORM\EntityManagerInterface;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ItemValidator;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ModifyHelper;
use WernerDweight\DoctrineCrudApiBundle\Service\Event\DoctrineCrudApiEventDispatcher;
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

    /** @var DoctrineCrudApiEventDispatcher */
    private $eventDispatcher;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ItemValidator */
    private $itemValidator;

    /** @var ModifyHelper */
    private $modifyHelper;

    /**
     * Creator constructor.
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        Formatter $formatter,
        DoctrineCrudApiEventDispatcher $eventDispatcher,
        EntityManagerInterface $entityManager,
        ItemValidator $itemValidator,
        ModifyHelper $modifyHelper
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->formatter = $formatter;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->itemValidator = $itemValidator;
        $this->modifyHelper = $modifyHelper;
    }

    /**
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function createItem(): RA
    {
        $this->parameterResolver->resolveCreate();
        $fieldValues = $this->parameterResolver->getRA(ParameterEnum::FIELDS);
        $item = $this->modifyHelper->create($fieldValues);

        $this->eventDispatcher->dispatchPreValidate($item);
        $this->itemValidator->validate($item);

        $this->eventDispatcher->dispatchPrePersist($item);
        $this->entityManager->persist($item);
        $this->modifyHelper->getNestedItems()->walk(function (ApiEntityInterface $nestedItem): void {
            $this->entityManager->persist($nestedItem);
        });
        $this->entityManager->flush();

        $this->eventDispatcher->dispatchPostCreate($item);
        return $this->formatter->format(
            $item,
            $this->parameterResolver->getRAOrNull(ParameterEnum::RESPONSE_STRUCTURE),
            $this->parameterResolver->getEntityPrefix()
        );
    }
}
