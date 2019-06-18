<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use WernerDweight\DoctrineCrudApiBundle\Exception\MappingResolverException;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class MappingResolver
{
    /** @var RelationResolver */
    private $relationResolver;

    /**
     * MappingResolver constructor.
     *
     * @param RelationResolver $relationResolver
     */
    public function __construct(RelationResolver $relationResolver)
    {
        $this->relationResolver = $relationResolver;
    }

    /**
     * @param string|null $value
     *
     * @return \DateTime|null
     *
     * @throws \Exception
     */
    private function resolveDateTime(?string $value): ?\DateTime
    {
        if (true === empty($value) || ParameterEnum::NULL_VALUE === $value) {
            return null;
        }
        return new \DateTime(
            // remove localized timezone (some browsers use localized names)
            (string)((new Stringy($value))->eregReplace('^([^\(]*)\s(.*$', '\\1'))
        );
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function isRelationType(string $type): bool
    {
        $relationTypes = [
            ClassMetadataInfo::ONE_TO_ONE,
            ClassMetadataInfo::MANY_TO_ONE,
            ClassMetadataInfo::TO_ONE,
            ClassMetadataInfo::ONE_TO_MANY,
            ClassMetadataInfo::MANY_TO_MANY,
            ClassMetadataInfo::TO_MANY,
        ];
        return in_array((int)$type, $relationTypes, true);
    }

    /**
     * @param RA     $configuration
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function resolveObject(RA $configuration, $value, string $type)
    {
        if (true === in_array($type, [Type::DATETIME, Type::DATE, Type::TIME], true)) {
            return $this->resolveDateTime($value);
        }
        if (true === $this->isRelationType($type)) {
            return $this->relationResolver->resolveRelation($configuration, $value, $type);
        }
        throw new MappingResolverException(MappingResolverException::EXCEPTION_UNKNOWN_MAPPING_TYPE, [$type]);
    }

    /**
     * @param RA     $configuration
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function resolveValueByType(RA $configuration, $value, string $type)
    {
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
        return $this->resolveObject($configuration, $value, $type);
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
        $type = (string)$configuration->get(QueryBuilderDecorator::DOCTRINE_ASSOCIATION_TYPE);

        return $this->resolveValueByType($configuration, $value, $type);
    }
}
