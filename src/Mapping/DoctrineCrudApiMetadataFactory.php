<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Exception\DoctrineCrudApiMetadataFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Annotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Chain;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\DoctrineCrudApiDriverInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Xml;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ConfigurationManager;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class DoctrineCrudApiMetadataFactory
{
    /** @var string */
    private const DRIVER_SUFFIX = 'Driver';
    /** @var string */
    private const SIMPLIFIED_DRIVER_SUFFIX = 'Simplified';
    /** @var string */
    private const CACHE_NAMESPACE = 'DOCTRINE_CRUD_API_CLASSMETADATA';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DoctrineCrudApiDriverFactory */
    private $driverFactory;

    /** @var DoctrineCrudApiDriverInterface|null */
    private $driver;

    /** @var ConfigurationManager */
    private $configurationManager;

    /**
     * DoctrineCrudApiMetadataFactory constructor.
     *
     * @param EntityManagerInterface       $entityManager
     * @param DoctrineCrudApiDriverFactory $driverFactory
     * @param ConfigurationManager         $configurationManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        DoctrineCrudApiDriverFactory $driverFactory,
        ConfigurationManager $configurationManager
    ) {
        $this->entityManager = $entityManager;
        $this->driverFactory = $driverFactory;
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param MappingDriver $mappingDriver
     *
     * @return DoctrineCrudApiDriverInterface
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getCustomDriver(MappingDriver $mappingDriver): DoctrineCrudApiDriverInterface
    {
        $shortDriverName = new Stringy(get_class($mappingDriver));
        $shortDriverName = $shortDriverName->substring($shortDriverName->getPositionOfLastSubstring('\\') + 1);

        if ($mappingDriver instanceof MappingDriverChain) {
            $driver = new Chain();
            foreach ($mappingDriver->getDrivers() as $namespace => $childDriver) {
                $driver->addDriver($this->getCustomDriver($childDriver), $namespace);
            }
            if (null !== $mappingDriver->getDefaultDriver()) {
                $driver->setDefaultDriver($this->getCustomDriver($mappingDriver->getDefaultDriver()));
            }
            return $driver;
        }

        $shortDriverName = $shortDriverName->substring(
            0,
            $shortDriverName->getPositionOfSubstring(self::DRIVER_SUFFIX)
        );
        $simplifiedPosition = $shortDriverName->getPositionOfSubstring(self::SIMPLIFIED_DRIVER_SUFFIX);
        $isSimplified = null !== $simplifiedPosition;
        if (true === $isSimplified) {
            $shortDriverName = $shortDriverName->substring(
                $simplifiedPosition + strlen(self::SIMPLIFIED_DRIVER_SUFFIX)
            );
        }

        $driver = $this->driverFactory->get((string)$shortDriverName);
        $driver->setOriginalDriver($mappingDriver);
        if ($driver instanceof Xml) {
            /** @var XmlDriver $typedMappingDriver */
            $typedMappingDriver = $mappingDriver;
            $driver->setLocator($typedMappingDriver->getLocator());
            return $driver;
        }

        if ($driver instanceof Annotation) {
            $driver->setAnnotationReader(new AnnotationReader());
            return $driver;
        }

        throw new DoctrineCrudApiMetadataFactoryException(
            DoctrineCrudApiMetadataFactoryException::UNEXPECTED_DRIVER,
            [get_class($driver)]
        );
    }

    /**
     * @return DoctrineCrudApiDriverInterface
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getDriver(): DoctrineCrudApiDriverInterface
    {
        if (null === $this->driver) {
            $implementation = $this->entityManager->getConfiguration()->getMetadataDriverImpl();
            if (null === $implementation) {
                throw new DoctrineCrudApiMetadataFactoryException(
                    DoctrineCrudApiMetadataFactoryException::UNKNOWN_DEFAULT_DRIVER_IMPLEMENTATION
                );
            }
            $this->driver = $this->getCustomDriver($implementation);
        }
        return $this->driver;
    }

    /**
     * @return RA
     */
    private function createConfigurationObject(): RA
    {
        return (new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::LISTABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::DEFAULT_LISTABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::CREATABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::UPDATABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::UPDATABLE_NESTED, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::METADATA, new RA())
        ;
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return DoctrineCrudApiMetadataFactory
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

        $config = $this->createConfigurationObject();
        if (null !== $reflectionClass) {
            $config = (new RA(class_parents($metadata->name)))
                ->reverse()
                ->reduce(function (RA $carry, string $className) use ($metadataFactory): RA {
                    if (true === $metadataFactory->hasMetadataFor($className)) {
                        $classMetadata = $this->entityManager->getClassMetadata($className);
                        return $this->getDriver()->readMetadata($classMetadata, $carry);
                    }
                    return $carry;
                }, $config);
            $config = $this->getDriver()->readMetadata($metadata, $config);
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
