<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\ORM\EntityManagerInterface;
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

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * ConfigurationManager constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->configuration = new RA();
    }

    private function loadConfigurationForEntityClass(string $class): RA
    {
        dump($this->entityManager->getClassMetadata($class));
        exit;
    }

    /**
     * @param string $class
     * @return RA
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getConfigurationForEntityClass(string $class): RA
    {
        if (true !== $this->configuration->hasKey($class)) {
            $this->configuration->set($class, $this->loadConfigurationForEntityClass($class));
        }
        $configuration = $this->configuration->getRAOrNull($class);
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
     * @return RA
     */
    public function getConfigurationForEntity(ApiEntityInterface $entity): RA
    {
        $className = (new Stringy(get_class($entity)))->replace(self::PROXY_PREFIX, '');
        return $this->getConfigurationForEntityClass((string)$className);
    }

    public function getFieldMetadata(RA $configuration, string $field): ?RA
    {

    }
}
