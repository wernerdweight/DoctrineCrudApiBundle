<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\ConfigurationManagerException;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ConfigurationManager
{
    /** @var string */
    private const PROXY_PREFIX = 'Proxies\\__CG__\\';

    /** @var RA */
    private $configuration;

    /**
     * ConfigurationManager constructor.
     */
    public function __construct()
    {
        $this->configuration = new RA();
    }

    /**
     * @param string                  $class
     * @param DoctrineCrudApiMetadata $metadata
     *
     * @return ConfigurationManager
     */
    public function setConfiguration(string $class, DoctrineCrudApiMetadata $metadata): self
    {
        $this->configuration->set($class, $metadata);
        return $this;
    }

    /**
     * @param string $class
     *
     * @return DoctrineCrudApiMetadata
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getConfigurationForEntityClass(string $class): DoctrineCrudApiMetadata
    {
        /** @var DoctrineCrudApiMetadata|null $configuration */
        $configuration = $this->configuration->get($class);
        if (null === $configuration) {
            throw new ConfigurationManagerException(
                ConfigurationManagerException::EXCEPTION_NO_CONFIGURATION_FOR_ENTITY,
                [$class]
            );
        }
        return $configuration;
    }

    /**
     * @param ApiEntityInterface $entity
     *
     * @return DoctrineCrudApiMetadata
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getConfigurationForEntity(ApiEntityInterface $entity): DoctrineCrudApiMetadata
    {
        $className = (new Stringy(get_class($entity)))->replace(self::PROXY_PREFIX, '');
        return $this->getConfigurationForEntityClass((string)$className);
    }
}
