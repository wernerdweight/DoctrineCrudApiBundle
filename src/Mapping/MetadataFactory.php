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
    /**
     * @var string
     */
    public const CACHE_NAMESPACE = 'DOCTRINE_CRUD_API_CLASSMETADATA';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ConfigurationManager
     */
    private $configurationManager;

    /**
     * @var MetadataDriverFactory
     */
    private $driverFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationManager $configurationManager,
        MetadataDriverFactory $driverFactory
    ) {
        $this->entityManager = $entityManager;
        $this->configurationManager = $configurationManager;
        $this->driverFactory = $driverFactory;
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     * @throws \Safe\Exceptions\SplException
     */
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

        $cacheDriver = $metadataFactory->getCacheDriver();
        if (null !== $cacheDriver) {
            $cacheKey = \Safe\sprintf('%s\\$%s', $metadata->name, self::CACHE_NAMESPACE);
            $cacheDriver->save($cacheKey, true === $isAccessible ? $configuration : null);
        }

        if (true === $isAccessible) {
            $this->configurationManager->setConfiguration($metadata->name, $configuration);
        }
        return $this;
    }
}
