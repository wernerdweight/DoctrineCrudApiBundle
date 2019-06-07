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

        return $queryBuilder
            ->select(\Safe\sprintf('DISTINCT %s', self::ROOT_ALIAS))
            ->getQuery()
            ->getResult();
    }
    
    public function getGroupedPortion(int $offset, int $limit, RA $orderBy, RA $filter, RA $groupBy): RA
    {
        // TODO: get current repo, apply filters, fetch groups, apply ordering and pagination, return results
    }
}
