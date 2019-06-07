<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use WernerDweight\DoctrineCrudApiBundle\Exception\ServiceEntityRepositoryFactoryException;
use WernerDweight\RA\Exception\RAException;
use WernerDweight\RA\RA;

class ServiceEntityRepositoryFactory
{
    /** @var RA */
    private $repositories;

    /**
     * ApiFactory constructor.
     *
     * @param RewindableGenerator $repositories
     */
    public function __construct(RewindableGenerator $repositories)
    {
        $this->repositories = new RA();
        /** @var \Generator $iterator */
        $iterator = $repositories->getIterator();
        while ($iterator->valid()) {
            /** @var ServiceEntityRepository $repository */
            $repository = $iterator->current();
            $this->repositories->set($repository->getClassName(), $repository);
            $iterator->next();
        }
    }

    /**
     * @param string $repositoryType
     *
     * @return ServiceEntityRepository
     *
     * @throws ServiceEntityRepositoryFactoryException
     * @throws RAException
     */
    public function get(string $className): ServiceEntityRepository
    {
        if (true !== $this->repositories->hasKey($className)) {
            throw new ServiceEntityRepositoryFactoryException(
                ServiceEntityRepositoryFactoryException::INVALID_ENTITY_CLASS,
                $className
            );
        }
        /** @var ServiceEntityRepository $repository */
        $repository = $this->repositories->get($className);
        return $repository;
    }
}
