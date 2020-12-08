<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor;

use Doctrine\ORM\EntityManagerInterface;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\DataManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Event\DoctrineCrudApiEventDispatcher;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\Formatter;
use WernerDweight\RA\RA;

class Deleter
{
    /** @var ParameterResolver */
    private $parameterResolver;

    /** @var DataManager */
    private $dataManager;

    /** @var Formatter */
    private $formatter;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DoctrineCrudApiEventDispatcher */
    private $eventDispatcher;

    /**
     * Deleter constructor.
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        DataManager $dataManager,
        Formatter $formatter,
        EntityManagerInterface $entityManager,
        DoctrineCrudApiEventDispatcher $eventDispatcher
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->dataManager = $dataManager;
        $this->formatter = $formatter;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function fetch(): ApiEntityInterface
    {
        return $this->dataManager->getItem(
            $this->parameterResolver->getString(ParameterEnum::PRIMARY_KEY)
        );
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getItem(): RA
    {
        $this->parameterResolver->resolveDelete();
        $item = $this->fetch();

        $this->eventDispatcher->dispatchPreDelete($item);
        $this->entityManager->remove($item);
        $this->entityManager->flush();
        $this->eventDispatcher->dispatchPostDelete($item);

        return $this->formatter->format($item, null, $this->parameterResolver->getEntityPrefix());
    }
}
