<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ConfigurationManager;
use WernerDweight\RA\RA;

class MetadataFactory
{
    /** @var string */
    private const CACHE_NAMESPACE = 'DOCTRINE_CRUD_API_CLASSMETADATA';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ConfigurationManager */
    private $configurationManager;

    /** @var MetadataDriverManager */
    private $driverManager;

    /**
     * MetadataFactory constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ConfigurationManager   $configurationManager
     * @param MetadataDriverManager  $driverManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationManager $configurationManager,
        MetadataDriverManager $driverManager
    ) {
        $this->entityManager = $entityManager;
        $this->configurationManager = $configurationManager;
        $this->driverManager = $driverManager;
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return MetadataFactory
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function extendClassMetadata(ClassMetadata $metadata): self
    {
        /** @var ClassMetadataFactory $metadataFactory */
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $reflectionClass = $metadata->reflClass;

        $config = $this->configurationManager->createConfigurationObject();
        if (null !== $reflectionClass) {
            $config = (new RA(class_parents($metadata->name)))
                ->reverse()
                ->reduce(function (RA $carry, string $className) use ($metadataFactory): RA {
                    if (true === $metadataFactory->hasMetadataFor($className)) {
                        $classMetadata = $this->entityManager->getClassMetadata($className);
                        return $this->driverManager->getDriver()->readMetadata($classMetadata, $carry);
                    }
                    return $carry;
                }, $config);
            $config = $this->driverManager->getDriver()->readMetadata($metadata, $config);
        }

        $cacheDriver = $metadataFactory->getCacheDriver();
        if (null !== $cacheDriver) {
            $cacheKey = \Safe\sprintf('%s\\$%s', $metadata->name, self::CACHE_NAMESPACE);
            $cacheDriver->save($cacheKey, $config->toArray());
        }

        $this->configurationManager->setConfiguration(
            $metadata->name,
            new DoctrineCrudApiMetadata($metadata->name, $metadata, $config)
        );
        return $this;
    }
}