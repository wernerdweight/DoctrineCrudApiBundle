<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\ConfigurationManagerException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\MetadataFactoryCacheProvider;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ConfigurationManager
{
    /**
     * @var string
     */
    private const PROXY_PREFIX = 'Proxies\\__CG__\\';

    private MetadataFactoryCacheProvider $cacheProvider;

    private RA $configuration;

    public function __construct(MetadataFactoryCacheProvider $cacheProvider)
    {
        $this->configuration = new RA();
        $this->cacheProvider = $cacheProvider;
    }

    public function setConfiguration(string $class, DoctrineCrudApiMetadata $metadata): self
    {
        $this->configuration->set($class, $metadata);
        return $this;
    }

    /**
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getConfigurationForEntityClass(string $class): DoctrineCrudApiMetadata
    {
        if (true !== $this->configuration->hasKey($class)) {
            $cached = $this->cacheProvider->recall($class);
            if (null !== $cached) {
                return $this
                    ->setConfiguration($class, $cached)
                    ->getConfigurationForEntityClass($class);
            }
            throw new ConfigurationManagerException(
                ConfigurationManagerException::EXCEPTION_NO_CONFIGURATION_FOR_ENTITY,
                [
                    $class,

                ]
            );
        }

        /** @var DoctrineCrudApiMetadata|null $configuration */
        $configuration = $this->configuration->get($class);
        if (null === $configuration) {
            throw new ConfigurationManagerException(
                ConfigurationManagerException::EXCEPTION_INVALID_CONFIGURATION_FOR_ENTITY,
                [
                    $class,

                ]
            );
        }
        return $configuration;
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getConfigurationForEntity(ApiEntityInterface $entity): DoctrineCrudApiMetadata
    {
        $className = (new Stringy(get_class($entity)))->replace(self::PROXY_PREFIX, '');
        return $this->getConfigurationForEntityClass((string)$className);
    }

    public function createConfigurationObject(): RA
    {
        return (new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::ACCESSIBLE, false)
            ->set(DoctrineCrudApiMappingTypeInterface::LISTABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::DEFAULT_LISTABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::CREATABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::CREATABLE_NESTED, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::UPDATABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::UPDATABLE_NESTED, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::METADATA, new RA())
        ;
    }
}
