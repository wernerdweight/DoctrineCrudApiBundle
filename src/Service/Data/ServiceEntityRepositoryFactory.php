<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use WernerDweight\DoctrineCrudApiBundle\Exception\ServiceEntityRepositoryFactoryException;
use WernerDweight\RA\Exception\RAException;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ServiceEntityRepositoryFactory
{
    /** @var RA */
    private $repositories;

    /**
     * ApiFactory constructor.
     *
     * @param RewindableGenerator<ServiceEntityRepository> $repositories
     */
    public function __construct(RewindableGenerator $repositories)
    {
        $this->repositories = new RA();
        /** @var \Generator<ServiceEntityRepository> $iterator */
        $iterator = $repositories->getIterator();
        while ($iterator->valid()) {
            /** @var ServiceEntityRepository $repository */
            $repository = $iterator->current();
            $entityName = (new Stringy($repository->getClassName()))->pregReplace('/Repository$/', '');
            $lastBackslashPosition = $entityName->getPositionOfLastSubstring('\\');
            if (null !== $lastBackslashPosition) {
                $entityName = $entityName->substring($lastBackslashPosition + 1);
            }
            $this->repositories->set((string)$entityName, $repository);
            $iterator->next();
        }
    }

    /**
     * @param string $className
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
                [$className]
            );
        }
        /** @var ServiceEntityRepository $repository */
        $repository = $this->repositories->get($className);
        return $repository;
    }
}
