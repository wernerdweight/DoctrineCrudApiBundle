<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ConfigurationManager;
use WernerDweight\RA\RA;

class MetadataFactory
{
    private EntityManagerInterface $entityManager;

    private ConfigurationManager $configurationManager;

    private MetadataDriverFactory $driverFactory;

    private MetadataFactoryCacheProvider $cacheProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationManager $configurationManager,
        MetadataDriverFactory $driverFactory,
        MetadataFactoryCacheProvider $cacheProvider
    ) {
        $this->entityManager = $entityManager;
        $this->configurationManager = $configurationManager;
        $this->driverFactory = $driverFactory;
        $this->cacheProvider = $cacheProvider;
    }

    public function extendClassMetadata(ClassMetadata $metadata): self
    {
        /** @var ClassMetadataFactory $metadataFactory */
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $reflectionClass = $metadata->reflClass;

        $config = $this->configurationManager->createConfigurationObject();
        if (null !== $reflectionClass) {
            $config = (new RA(\Safe\class_parents($metadata->name)))
                ->reverse()
                ->reduce(function (RA $carry, string $className) use ($metadataFactory): RA {
                    if (true === $metadataFactory->hasMetadataFor($className)) {
                        $classMetadata = $this->entityManager->getClassMetadata($className);
                        return $this->driverFactory->getDriver()
                            ->readMetadata($classMetadata, $carry);
                    }
                    return $carry;
                }, $config);
            $config = $this->driverFactory->getDriver()
                ->readMetadata($metadata, $config);
        }

        $isAccessible = $config->getBool(DoctrineCrudApiMappingTypeInterface::ACCESSIBLE);
        $configuration = new DoctrineCrudApiMetadata($metadata->name, $metadata, $config);

        $this->cacheProvider->store($metadata, true === $isAccessible ? $configuration : null);

        if (true === $isAccessible) {
            $this->configurationManager->setConfiguration($metadata->name, $configuration);
        }
        return $this;
    }
}
