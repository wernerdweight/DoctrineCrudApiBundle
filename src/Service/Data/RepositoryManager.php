<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Bundle\DoctrineBundle\Repository\ContainerRepositoryFactory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;

class RepositoryManager
{
    /** @var ServiceEntityRepository */
    private $currentRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ContainerRepositoryFactory */
    private $containerRepositoryFactory;

    /** @var ParameterResolver */
    private $parameterResolver;

    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerRepositoryFactory $containerRepositoryFactory,
        ParameterResolver $parameterResolver
    ) {
        $this->entityManager = $entityManager;
        $this->parameterResolver = $parameterResolver;
        $this->containerRepositoryFactory = $containerRepositoryFactory;
    }

    /**
     * @return ServiceEntityRepository
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getCurrentRepository(): ServiceEntityRepository
    {
        if (null === $this->currentRepository) {
            $this->currentRepository = $this->containerRepositoryFactory->getRepository(
                $this->entityManager,
                $this->parameterResolver->getString(ParameterEnum::ENTITY_NAME)
            );
        }
        return $this->currentRepository;
    }

    /**
     * @return ClassMetadata
     */
    public function getCurrentMetadata(): ClassMetadata
    {
        $this->entityManager->getClassMetadata(ParameterEnum::ENTITY_NAME);
    }
}
