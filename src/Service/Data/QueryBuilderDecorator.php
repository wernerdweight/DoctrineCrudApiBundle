<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use WernerDweight\DoctrineCrudApiBundle\Exception\FilteringException;
use WernerDweight\DoctrineCrudApiBundle\Service\ConditionGenerator\ConditionGeneratorFactory;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class QueryBuilderDecorator
{
    /** @var string */
    public const DOCTRINE_TARGET_ENTITY = 'targetEntity';
    /** @var string */
    public const AGGREGATE_PREFIX = '_aggregate';
    /** @var string */
    public const AGGREGATE_FUNCTION_SEPARATOR = '_';

    /** @var FilteringDecorator */
    private $filteringDecorator;

    /** @var FilteringHelper */
    private $filteringHelper;

    /** @var RelationJoiner */
    private $relationJoiner;

    /**
     * QueryBuilderDecorator constructor.
     * @param FilteringDecorator $filteringDecorator
     * @param FilteringHelper $filteringHelper
     * @param RelationJoiner $relationJoiner
     */
    public function __construct(
        FilteringDecorator $filteringDecorator,
        FilteringHelper $filteringHelper,
        RelationJoiner $relationJoiner
    ) {
        $this->filteringDecorator = $filteringDecorator;
        $this->filteringHelper = $filteringHelper;
        $this->relationJoiner = $relationJoiner;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA           $filterData
     *
     * @return QueryBuilderDecorator
     */
    public function applyFiltering(QueryBuilder $queryBuilder, RA $filterData): self
    {
        if (true === $filterData->hasKey(ParameterEnum::FILTER_CONDITIONS)) {
            $logic = $this->filteringHelper->getFilteringLogic($filterData);
            $conditions = $this->filteringDecorator->prepareFilteringConditions(
                $queryBuilder,
                $filterData->getRA(ParameterEnum::FILTER_CONDITIONS),
                0
            );
            ParameterEnum::FILTER_LOGIC_AND === $logic
                ? $queryBuilder->andWhere(...$conditions->toArray())
                : $queryBuilder->orWhere(...$conditions->toArray());
        }
        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA           $orderings
     *
     * @return QueryBuilderDecorator
     */
    public function applyOrdering(QueryBuilder $queryBuilder, RA $orderings): self
    {
        $orderings->walk(function (RA $orderData) use ($queryBuilder): void {
            /** @var Stringy $field */
            $field = $orderData->get(ParameterEnum::ORDER_BY_FIELD);
            $direction = $orderData->getString(ParameterEnum::ORDER_BY_DIRECTION);

            if (true !== in_array($direction, ParameterEnum::AVAILABLE_ORDERING_DIRECTIONS, true)) {
                throw new FilteringException(
                    FilteringException::EXCEPTION_INVALID_ORDERING_DIRECTION,
                    [$direction, implode(', ', ParameterEnum::AVAILABLE_ORDERING_DIRECTIONS)]
                );
            }

            if (true === $this->filteringHelper->isEmbed($field)) {
                $queryBuilder->addOrderBy((string)$field, $direction);
                return;
            }

            $this->relationJoiner->joinRequiredRelations($queryBuilder, $field);
            $queryBuilder->addOrderBy((string)($this->filteringHelper->getFilteringPathForField($field)), $direction);
        });
        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int          $offset
     * @param int          $limit
     *
     * @return QueryBuilderDecorator
     */
    public function applyPagination(QueryBuilder $queryBuilder, int $offset, int $limit): self
    {
        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Stringy      $field
     *
     * @return QueryBuilderDecorator
     *
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     */
    public function applyGroupping(QueryBuilder $queryBuilder, Stringy $field): self
    {
        if (true === $this->filteringHelper->isEmbed($field)) {
            $queryBuilder->groupBy(\Safe\sprintf('%s.%s', DataManager::ROOT_ALIAS, $field));
            return $this;
        }
        $this->relationJoiner->joinRequiredRelations($queryBuilder, $field);
        $queryBuilder->addGroupBy((string)($this->filteringHelper->getFilteringPathForField($field)));
        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA           $aggregates
     *
     * @return QueryBuilderDecorator
     */
    public function applyAggregates(QueryBuilder $queryBuilder, RA $aggregates): self
    {
        $aggregates->walk(function (RA $aggregateData) use ($queryBuilder): void {
            $function = $aggregateData->getString(ParameterEnum::GROUP_BY_AGGREGATE_FUNCTION);
            $field = $aggregateData->getString(ParameterEnum::GROUP_BY_AGGREGATE_FIELD);
            $queryBuilder->addSelect(\Safe\sprintf(
                '%s(%s.%s) AS %s%s%s%s%s',
                $function,
                DataManager::ROOT_ALIAS,
                $field,
                self::AGGREGATE_PREFIX,
                self::AGGREGATE_FUNCTION_SEPARATOR,
                $function,
                self::AGGREGATE_FUNCTION_SEPARATOR,
                $field
            ));
        });
        return $this;
    }
}
