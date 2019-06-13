<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use WernerDweight\DoctrineCrudApiBundle\Exception\DoctrineCrudApiMetadataFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Annotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Chain;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\DoctrineCrudApiDriverInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Xml;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class DoctrineCrudApiMetadataFactory
{
    /** @var string */
    private const DRIVER_CHAIN_CLASSNAME = 'DriverChain';
    /** @var string */
    private const DRIVER_SUFFIX = 'Driver';
    /** @var string */
    private const SIMPLIFIED_DRIVER_SUFFIX = 'Simplified';

    /** @var EntityManager */
    private $entityManager;

    /** @var DoctrineCrudApiDriverFactory */
    private $driverFactory;

    /** @var DoctrineCrudApiDriverInterface|null */
    private $driver;

    /**
     * DoctrineCrudApiMetadataFactory constructor.
     * @param EntityManagerInterface $entityManager
     * @param DoctrineCrudApiDriverFactory $driverFactory¨
     */
    public function __construct(EntityManagerInterface $entityManager, DoctrineCrudApiDriverFactory $driverFactory)
    {
        $this->entityManager = $entityManager;
        $this->driverFactory = $driverFactory;
    }

    /**
     * @param MappingDriver $mappingDriver
     * @return DoctrineCrudApiDriverInterface
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getCustomDriver(MappingDriver $mappingDriver): DoctrineCrudApiDriverInterface
    {
        $shortDriverName = new Stringy(get_class($mappingDriver));
        $shortDriverName = $shortDriverName->substring($shortDriverName->getPositionOfLastSubstring('\\') + 1);

        if ($mappingDriver instanceof MappingDriverChain || $shortDriverName === self::DRIVER_CHAIN_CLASSNAME) {
            $driver = new Chain();
            foreach ($mappingDriver->getDrivers() as $namespace => $childDriver) {
                $driver->addDriver($this->getCustomDriver($childDriver), $namespace);
            }
            if (null !== $mappingDriver->getDefaultDriver()) {
                $driver->setDefaultDriver($this->getCustomDriver($mappingDriver->getDefaultDriver()));
            }
            return $driver;
        }

        $shortDriverName = $shortDriverName->substring(0, $shortDriverName->getPositionOfSubstring(self::DRIVER_SUFFIX));
        $simplifiedPosition = $shortDriverName->getPositionOfSubstring(self::SIMPLIFIED_DRIVER_SUFFIX);
        $isSimplified = null !== $simplifiedPosition;
        if (true === $isSimplified) {
            $shortDriverName = $shortDriverName->substring($simplifiedPosition + strlen(self::SIMPLIFIED_DRIVER_SUFFIX));
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
            get_class($driver)
        );
    }

    /**
     * @return DoctrineCrudApiDriverInterface
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getDriver(): DoctrineCrudApiDriverInterface
    {
        if (null === $this->driver) {
            $this->driver = $this->getCustomDriver(
                $this->entityManager->getConfiguration()->getMetadataDriverImpl()
            );
        }
        return $this->driver;
    }

    public function extendClassMetadata(ClassMetadata $metadata): self
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $reflectionClass = $metadata->reflClass;

        $config = new RA();
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
            $cacheKey = \Safe\sprintf('%s\\$DOCTRINE_CRUD_API_CLASSMETADATA', $metadata->name);
            $cacheDriver->save($cacheKey, $config->toArray());
        }

        dump($config);exit;
    }
}
