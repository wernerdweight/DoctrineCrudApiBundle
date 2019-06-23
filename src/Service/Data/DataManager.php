<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\DataManagerException;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class DataManager
{
    /** @var string */
    private const NULL_GROUP = 'N/A';
    /** @var string */
    public const ROOT_ALIAS = 'this';

    /** @var RepositoryManager */
    private $repositoryManager;

    /** @var QueryBuilderDecorator */
    private $queryBuilderDecorator;

    /**
     * DataManager constructor.
     *
     * @param RepositoryManager     $repositoryManager
     * @param QueryBuilderDecorator $queryBuilderDecorator
     */
    public function __construct(RepositoryManager $repositoryManager, QueryBuilderDecorator $queryBuilderDecorator)
    {
        $this->repositoryManager = $repositoryManager;
        $this->queryBuilderDecorator = $queryBuilderDecorator;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param RA  $orderBy
     * @param RA  $filters
     *
     * @return RA
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getPortion(int $offset, int $limit, RA $orderBy, RA $filters): RA
    {
        $queryBuilder = $this->repositoryManager->getCurrentRepository()->createQueryBuilder(self::ROOT_ALIAS);

        $this->queryBuilderDecorator
            ->applyFiltering($queryBuilder, $filters)
            ->applyOrdering($queryBuilder, $orderBy)
            ->applyPagination($queryBuilder, $offset, $limit);

        return new RA(
            $queryBuilder
                ->select(\Safe\sprintf('DISTINCT %s', self::ROOT_ALIAS))
                ->getQuery()
                ->getResult(),
            RA::RECURSIVE
        );
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param RA  $orderBy
     * @param RA  $filter
     * @param RA  $groupBy
     *
     * @return RA
     *
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getGroupedPortion(int $offset, int $limit, RA $orderBy, RA $filter, RA $groupBy): RA
    {
        /** @var RA $primaryGroup */
        $primaryGroup = $groupBy->shift();
        /** @var Stringy $field */
        $field = $primaryGroup->get(ParameterEnum::GROUP_BY_FIELD);
        $aggregates = $primaryGroup->getRA(ParameterEnum::GROUP_BY_AGGREGATES);

        $queryBuilder = $this->repositoryManager->getCurrentRepository()
            ->createQueryBuilder(self::ROOT_ALIAS)
            ->select(\Safe\sprintf('%s AS value', $field));
        $this->queryBuilderDecorator
            ->applyFiltering($queryBuilder, $filter)
            ->applyGroupping($queryBuilder, $field)
            ->applyAggregates($queryBuilder, $aggregates)
            ->applyOrdering($queryBuilder, new RA([$primaryGroup]))
            ->applyPagination($queryBuilder, $offset, $limit);

        $groups = new RA($queryBuilder->getQuery()->getResult(), RA::RECURSIVE);

        return $groups->map(function (RA $group) use ($groupBy, $filter, $field, $limit, $orderBy): RA {
            $filteringConditions = new RA();
            $filteringConditions->push((new RA())
                ->set(ParameterEnum::FILTER_FIELD, $field)
                ->set(ParameterEnum::FILTER_VALUE, $group->get(ParameterEnum::FILTER_VALUE))
                ->set(
                    ParameterEnum::FILTER_OPERATOR,
                    null === $group->get(ParameterEnum::FILTER_VALUE)
                        ? ParameterEnum::FILTER_OPERATOR_IS_NULL
                        : ParameterEnum::FILTER_OPERATOR_EQUAL
                ));
            if ($filter->length() > 0) {
                $filteringConditions->push($filter);
            }
            $groupConditions = (new RA())
                ->set(ParameterEnum::FILTER_LOGIC, ParameterEnum::FILTER_LOGIC_AND)
                ->set(ParameterEnum::FILTER_CONDITIONS, $filteringConditions);
            return $group->set(
                ParameterEnum::GROUP_BY_ITEMS,
                $groupBy->length() > 0
                    ? $this->getGroupedPortion(0, $limit, $groupBy, $orderBy, $groupConditions)
                    : $this->getPortion(0, $limit, $orderBy, $groupConditions)
            );
        });
    }

    /**
     * @param RA      $filter
     * @param RA|null $groupBy
     *
     * @return int
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getCount(RA $filter, ?RA $groupBy): int
    {
        $queryBuilder = $this->repositoryManager->getCurrentRepository()
            ->createQueryBuilder(self::ROOT_ALIAS)
            ->select('COUNT(DISTINCT(this))');
        $this->queryBuilderDecorator->applyFiltering($queryBuilder, $filter);

        if (null !== $groupBy) {
            /** @var RA $primaryGroup */
            $primaryGroup = $groupBy->first();
            $field = $primaryGroup->get(ParameterEnum::GROUP_BY_FIELD);
            $this->queryBuilderDecorator->applyGroupping($queryBuilder, $field);
            $queryBuilder->select(\Safe\sprintf('COUNT(DISTINCT(%s))', $field, self::NULL_GROUP));
            $queryBuilder->resetDQLPart(ParameterEnum::GROUP_BY);
        }

        return (int)($queryBuilder->getQuery()->getSingleScalarResult());
    }

    /**
     * @param string $primaryKey
     *
     * @return ApiEntityInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getItem(string $primaryKey): ApiEntityInterface
    {
        /** @var ApiEntityInterface|null $item */
        $item = $this->repositoryManager->getCurrentRepository()->find($primaryKey);
        if (null === $item) {
            throw new DataManagerException(DataManagerException::UNKNOWN_ENTITY_REQUESTED);
        }
        return $item;
    }
}
