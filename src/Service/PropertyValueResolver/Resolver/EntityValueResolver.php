<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\MappingResolverException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\FilteringHelper;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\QueryBuilderDecorator;
use WernerDweight\RA\RA;

final class EntityValueResolver implements PropertyValueResolverInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param class-string $className
     */
    public function resolve(RA $itemData, string $className): ApiEntityInterface
    {
        $id = $itemData->get(FilteringHelper::IDENTIFIER_FIELD_NAME);
        /** @var ApiEntityInterface|null $item */
        $item = $this->entityManager->find($className, $id);
        if (null === $item) {
            throw new MappingResolverException(MappingResolverException::EXCEPTION_UNKNOWN_RELATED_ENTITY, [
                $className,
                $id,
            ]);
        }
        return $item;
    }

    /**
     * @param RA|string|int $value
     */
    public function getPropertyValue($value, RA $configuration): ?ApiEntityInterface
    {
        $className = $this->getPropertyValueTargetEntity($configuration);

        if ($value instanceof RA) {
            return $this->resolve($value, $className);
        }
        return $this->resolve(new RA([
            FilteringHelper::IDENTIFIER_FIELD_NAME => $value,
        ]), $className);
    }

    /**
     * @return (int|string)[]
     */
    public function getPropertyTypes(): array
    {
        return [
            DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY,
            ClassMetadataInfo::TO_ONE,
            ClassMetadataInfo::ONE_TO_ONE,
            ClassMetadataInfo::MANY_TO_ONE,
        ];
    }

    /**
     * @return class-string
     */
    private function getPropertyValueTargetEntity(RA $configuration): string
    {
        if (true !== $configuration->hasKey(DoctrineCrudApiMappingTypeInterface::METADATA_CLASS)) {
            if (true !== $configuration->hasKey(QueryBuilderDecorator::DOCTRINE_TARGET_ENTITY)) {
                throw new MappingResolverException(MappingResolverException::EXCEPTION_MISSING_TARGET_ENTITY, [
                    implode(', ', $this->getPropertyTypes()),
                ]);
            }
            /** @var class-string $className */
            $className = $configuration->getString(QueryBuilderDecorator::DOCTRINE_TARGET_ENTITY);
            return $className;
        }
        /** @var class-string $className */
        $className = $configuration->getString(DoctrineCrudApiMappingTypeInterface::METADATA_CLASS);
        return $className;
    }
}
