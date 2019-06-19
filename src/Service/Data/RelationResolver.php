<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\MappingResolverException;
use WernerDweight\RA\RA;

class RelationResolver
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * MappingResolver constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param RA     $itemData
     * @param string $className
     *
     * @return ApiEntityInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function resolveEntity(RA $itemData, string $className): ApiEntityInterface
    {
        $id = $itemData->get(FilteringHelper::IDENTIFIER_FIELD_NAME);
        /** @var ApiEntityInterface|null $item */
        $item = $this->entityManager->find($className, $id);
        if (null === $item) {
            throw new MappingResolverException(
                MappingResolverException::EXCEPTION_UNKNOWN_RELATED_ENTITY,
                [$className, $id]
            );
        }
        return $item;
    }

    /**
     * @param RA     $value
     * @param string $className
     *
     * @return ArrayCollection
     */
    private function resolveToMany(RA $value, string $className): ArrayCollection
    {
        return new ArrayCollection(
            $value
                ->map(function (RA $itemData) use ($className): ApiEntityInterface {
                    return $this->resolveEntity($itemData, $className);
                })
                ->toArray()
        );
    }

    /**
     * @param RA|string|int $value
     * @param string        $className
     *
     * @return ApiEntityInterface
     */
    private function resolveToOne($value, string $className): ApiEntityInterface
    {
        if ($value instanceof RA) {
            return $this->resolveEntity($value, $className);
        }

        return $this->resolveEntity(new RA([FilteringHelper::IDENTIFIER_FIELD_NAME => $value]), $className);
    }

    /**
     * @param RA            $configuration
     * @param RA|string|int $value
     * @param string        $type
     *
     * @return ArrayCollection|ApiEntityInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function resolveRelation(RA $configuration, $value, string $type)
    {
        if (true !== $configuration->hasKey(QueryBuilderDecorator::DOCTRINE_TARGET_ENTITY)) {
            throw new MappingResolverException(MappingResolverException::EXCEPTION_MISSING_TARGET_ENTITY, [$type]);
        }
        $className = $configuration->getString(QueryBuilderDecorator::DOCTRINE_TARGET_ENTITY);
        if ((int)$type & ClassMetadataInfo::TO_MANY) {
            /** @var RA $typedValue */
            $typedValue = $value;
            return $this->resolveToMany($typedValue, $className);
        }
        return $this->resolveToOne($value, $className);
    }
}
