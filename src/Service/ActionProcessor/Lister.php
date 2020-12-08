<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor;

use WernerDweight\DoctrineCrudApiBundle\Service\Data\DataManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\ListingFormatter;
use WernerDweight\RA\RA;

class Lister
{
    /** @var ParameterResolver */
    private $parameterResolver;

    /** @var DataManager */
    private $dataManager;

    /** @var ListingFormatter */
    private $formatter;

    /**
     * Lister constructor.
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        DataManager $dataManager,
        ListingFormatter $formatter
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->dataManager = $dataManager;
        $this->formatter = $formatter;
    }

    /**
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
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
        return $this->dataManager->getGroupedPortion(...$arguments->push(clone $groupByParameter));
    }

    /**
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getItems(): RA
    {
        $this->parameterResolver->resolveList();
        $items = $this->fetch();
        return $this->formatter->formatListing($items);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function fetchCount(): int
    {
        $filter = $this->parameterResolver->getRA(ParameterEnum::FILTER);
        $groupBy = $this->parameterResolver->getRAOrNull(ParameterEnum::GROUP_BY);
        return $this->dataManager->getCount($filter, $groupBy);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getItemCount(): int
    {
        $this->parameterResolver->resolveList();
        return $this->fetchCount();
    }
}
