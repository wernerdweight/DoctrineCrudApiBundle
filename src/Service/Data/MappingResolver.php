<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\MappingResolverException;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class MappingResolver
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
        $id = $itemData->get(QueryBuilderDecorator::IDENTIFIER_FIELD_NAME);
        /** @var ApiEntityInterface $item */
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

        return $this->resolveEntity(new RA([QueryBuilderDecorator::IDENTIFIER_FIELD_NAME => $value]), $className);
    }

    /**
     * @param RA    $configuration
     * @param mixed $value
     *
     * @return mixed
     */
    public function resolveValue(RA $configuration, $value)
    {
        if (true !== $configuration->hasKey(QueryBuilderDecorator::DOCTRINE_ASSOCIATION_TYPE)) {
            throw new MappingResolverException(MappingResolverException::EXCEPTION_MISSING_MAPPING_TYPE);
        }
        $type = $configuration->getString(QueryBuilderDecorator::DOCTRINE_ASSOCIATION_TYPE);

        if (true === in_array($type, [Type::DATETIME, Type::DATE === $type, Type::TIME === $type], true)) {
            if (true === empty($value) || ParameterEnum::NULL_VALUE === $value) {
                return null;
            }
            return new \DateTime(
                // remove localized timezone (some browsers use localized names)
                (new Stringy($value))->eregReplace('^([^\(]*)\s(.*$', '\\1')
            );
        }
        if (true === in_array($type, [Type::INTEGER, Type::BIGINT, Type::SMALLINT], true)) {
            return ParameterEnum::EMPTY_VALUE !== $value ? (int)$value : null;
        }
        if (true === in_array($type, [Type::FLOAT, Type::DECIMAL], true)) {
            return ParameterEnum::EMPTY_VALUE !== $value ? (float)$value : null;
        }
        if (Type::BOOLEAN === $type) {
            return ParameterEnum::TRUE_VALUE === $value;
        }
        if (true === in_array($type, [Type::STRING, Type::TEXT], true)) {
            return ParameterEnum::EMPTY_VALUE !== $value ? (string)$value : null;
        }
        if (true === in_array(
            $type, [
                ClassMetadataInfo::ONE_TO_ONE,
                ClassMetadataInfo::MANY_TO_ONE,
                ClassMetadataInfo::TO_ONE,
                ClassMetadataInfo::ONE_TO_MANY,
                ClassMetadataInfo::MANY_TO_MANY,
                ClassMetadataInfo::TO_MANY,
            ],
            true
        )) {
            if (true !== $configuration->hasKey(QueryBuilderDecorator::DOCTRINE_TARGET_ENTITY)) {
                throw new MappingResolverException(MappingResolverException::EXCEPTION_MISSING_TARGET_ENTITY, [$type]);
            }
            $className = $configuration->getString(QueryBuilderDecorator::DOCTRINE_TARGET_ENTITY);
            if ($type & ClassMetadataInfo::TO_MANY) {
                return $this->resolveToMany($value, $className);
            }
            return $this->resolveToOne($value, $className);
        }

        throw new MappingResolverException(MappingResolverException::EXCEPTION_UNKNOWN_MAPPING_TYPE, [$type]);
    }
}
