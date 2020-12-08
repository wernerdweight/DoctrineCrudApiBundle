<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor;

use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\DataManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\Formatter;
use WernerDweight\RA\RA;

class Detailer
{
    /** @var ParameterResolver */
    private $parameterResolver;

    /** @var DataManager */
    private $dataManager;

    /** @var Formatter */
    private $formatter;

    /**
     * Detailer constructor.
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        DataManager $dataManager,
        Formatter $formatter
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->dataManager = $dataManager;
        $this->formatter = $formatter;
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
        $this->parameterResolver->resolveDetail();
        $item = $this->fetch();
        return $this->formatter->format(
            $item,
            $this->parameterResolver->getRAOrNull(ParameterEnum::RESPONSE_STRUCTURE),
            $this->parameterResolver->getEntityPrefix()
        );
    }
}
