<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor;

use WernerDweight\DoctrineCrudApiBundle\Service\Data\DataManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\Formatter;
use WernerDweight\RA\RA;

class Lister
{
    /** @var ParameterResolver */
    private $parameterResolver;

    /** @var DataManager */
    private $dataManager;

    /** @var Formatter */
    private $formatter;

    public function __construct(ParameterResolver $parameterResolver, DataManager $dataManager, Formatter $formatter)
    {
        $this->parameterResolver = $parameterResolver;
        $this->dataManager = $dataManager;
        $this->formatter = $formatter;
    }

    private function fetch(): RA
    {
        $arguments = new RA([
            $this->parameterResolver->getInt(ParameterEnum::OFFSET),
            $this->parameterResolver->getInt(ParameterEnum::LIMIT),
            $this->parameterResolver->getRA(ParameterEnum::ORDER_BY),
            $this->parameterResolver->getRA(ParameterEnum::FILTER),
        ]);

        $groupByParameter = $this->parameterResolver->getRAOrNull(ParameterEnum::GROUP_BY);
        if (null === $groupByParameter) {
            return $this->dataManager->getPortion(...$arguments);
        }
        return $this->dataManager->getGroupedPortion(...$arguments->push($groupByParameter));
    }
    
    public function getItems(): RA
    {
        $this->parameterResolver->resolveList();
        $items = $this->fetch();
        return $this->formatter->formatMany($items);
    }
}
