<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\CurrentEntityResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\Exception\RAException;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class RepositoryManager
{
    /** @var Stringy */
    private $currentEntityName;

    /** @var Stringy */
    private $currentEntityFQCN;

    /** @var ServiceEntityRepository|null */
    private $currentRepository;

    /** @var ClassMetadata|null */
    private $currentMetadata;

    /** @var RA|null */
    private $currentMappings;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ServiceEntityRepositoryFactory */
    private $repositoryFactory;

    /** @var CurrentEntityResolver */
    private $currentEntityResolver;

    /**
     * RepositoryManager constructor.
     *
     * @param EntityManagerInterface         $entityManager
     * @param ServiceEntityRepositoryFactory $repositoryFactory
     * @param CurrentEntityResolver          $currentEntityResolver
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ServiceEntityRepositoryFactory $repositoryFactory,
        CurrentEntityResolver $currentEntityResolver
    ) {
        $this->entityManager = $entityManager;
        $this->currentEntityResolver = $currentEntityResolver;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * @return Stringy
     *
     * @throws RAException
     */
    public function getCurrentEntityName(): Stringy
    {
        if (null === $this->currentEntityName) {
            $this->currentEntityName = $this->currentEntityResolver->getCurrentEntity();
        }
        return $this->currentEntityName;
    }

    /**
     * @return Stringy
     */
    public function getCurrentEntityFQCN(): Stringy
    {
        if (null === $this->currentEntityFQCN) {
            $this->currentEntityFQCN = $this->currentEntityResolver->getCurrentEntityFQCN();
        }
        return $this->currentEntityFQCN;
    }

    /**
     * @return ServiceEntityRepository
     *
     * @throws RAException
     */
    public function getCurrentRepository(): ServiceEntityRepository
    {
        if (null === $this->currentRepository) {
            $this->currentRepository = $this->repositoryFactory->get((string)$this->getCurrentEntityName());
        }
        return $this->currentRepository;
    }

    /**
     * @param string $entityFQCN
     *
     * @return ClassMetadata
     */
    private function getEntityMetadata(string $entityFQCN): ClassMetadata
    {
        return $this->entityManager->getClassMetadata($entityFQCN);
    }

    /**
     * @return ClassMetadata
     */
    public function getCurrentMetadata(): ClassMetadata
    {
        if (null === $this->currentMetadata) {
            $this->currentMetadata = $this->getEntityMetadata((string)$this->getCurrentEntityFQCN());
        }
        return $this->currentMetadata;
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return RA
     */
    private function getEntityMappings(ClassMetadata $metadata): RA
    {
        return (new RA())
            ->merge(
                new RA($metadata->fieldMappings, RA::RECURSIVE),
                new RA($metadata->associationMappings, RA::RECURSIVE)
            );
    }

    /**
     * @return RA
     */
    public function getCurrentMappings(): RA
    {
        if (null === $this->currentMappings) {
            $this->currentMappings = $this->getEntityMappings($this->getCurrentMetadata());
        }
        return $this->currentMappings;
    }

    /**
     * @param Stringy $field
     * @param RA|null $mappings
     *
     * @return RA|null
     *
     * @throws RAException
     */
    public function getMappingForField(Stringy $field, ?RA $mappings = null): ?RA
    {
        if (null === $mappings) {
            $mappings = $this->getCurrentMappings();
        }

        $firstDotPosition = $field->getPositionOfSubstring(ParameterEnum::FIELD_SEPARATOR);
        if (null === $firstDotPosition) {
            return null;
        }

        $root = (clone $field)->substring(0, $firstDotPosition);
        $field = $field->substring($firstDotPosition + 1);
        if (true !== $mappings->hasKey((string)$root)) {
            return null;
        }

        $mapping = $mappings->getRA((string)$root);
        if (true !== $mapping->hasKey(QueryBuilderDecorator::DOCTRINE_TARGET_ENTITY)) {
            return null;
        }

        $targetEntity = $mapping->getString(QueryBuilderDecorator::DOCTRINE_TARGET_ENTITY);
        $firstDotPosition = $field->getPositionOfSubstring(ParameterEnum::FIELD_SEPARATOR);
        $fieldMappings = $this->getEntityMappings($this->getEntityMetadata($targetEntity));
        if (null !== $firstDotPosition) {
            return $this->getMappingForField($field, $fieldMappings);
        }

        return true === $fieldMappings->hasKey((string)$field)
            ? $fieldMappings->getRA((string)$field)
            : null;
    }
}
