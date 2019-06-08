<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Bundle\DoctrineBundle\Repository\ContainerRepositoryFactory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\RA\RA;

class DataManager
{
    /** @var string */
    public const ROOT_ALIAS = 'this';

    /** @var RepositoryManager */
    private $repositoryManager;

    /** @var QueryBuilderDecorator */
    private $queryBuilderDecorator;

    public function __construct(RepositoryManager $repositoryManager, QueryBuilderDecorator $queryBuilderDecorator)
    {
        $this->repositoryManager = $repositoryManager;
        $this->queryBuilderDecorator = $queryBuilderDecorator;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param RA $orderBy
     * @param RA $filters
     * @return RA
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
     * @param RA $orderBy
     * @param RA $filter
     * @param RA $groupBy
     * @return RA
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getGroupedPortion(int $offset, int $limit, RA $orderBy, RA $filter, RA $groupBy): RA
    {
        /** @var RA $primaryGroup */
        $primaryGroup = $groupBy->shift();
        $field = $primaryGroup->getString(ParameterEnum::GROUP_BY_FIELD);
        $direction = $primaryGroup->getString(ParameterEnum::GROUP_BY_DIRECTION);
        $aggregates = $primaryGroup->getRA(ParameterEnum::GROUP_BY_AGGREGATES);

        $queryBuilder = $this->repositoryManager->getCurrentRepository()
            ->createQueryBuilder(self::ROOT_ALIAS)
            ->select(\Safe\sprintf('%s AS value', $field));
        $this->queryBuilderDecorator
            ->applyGroupping($queryBuilder, $field)
            ->applyAggregates($queryBuilder, $aggregates)
            ->applyOrdering($queryBuilder, $primaryGroup)
            ->applyPagination($queryBuilder, $offset, $limit);

        $groups = new RA($queryBuilder->getQuery()->getResult(), RA::RECURSIVE);

        return $groups->map(function (RA $group) use ($groupBy, $filter, $field, $limit, $orderBy): RA {
            $groupConditions = (new RA())
                ->set(ParameterEnum::FILTER_LOGIC, ParameterEnum::FILTER_LOGIC_AND)
                ->set(ParameterEnum::FILTER, (new RA())
                    ->set(ParameterEnum::FILTER_FIELD, $field)
                    ->set(ParameterEnum::FILTER_VALUE, $group->get(ParameterEnum::FILTER_VALUE))
                    ->set(
                        ParameterEnum::FILTER_OPERATOR,
                        null === $group->get(ParameterEnum::FILTER_VALUE)
                            ? ParameterEnum::FILTER_OPERATOR_IS_NULL
                            : ParameterEnum::FILTER_OPERATOR_EQUAL
                    )
                );
            return $group->set(
                ParameterEnum::GROUP_BY_ITEMS,
                $groupBy->length() > 0
                    ? $this->getGroupedPortion(0, $limit, $groupBy, $orderBy, $groupConditions)
                    : $this->getPortion(0, $limit, $orderBy, $groupConditions)
            );
        });
    }
}
