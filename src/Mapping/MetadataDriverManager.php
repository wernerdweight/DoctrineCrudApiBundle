<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use WernerDweight\DoctrineCrudApiBundle\Exception\MetadataFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Annotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Chain;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\DoctrineCrudApiDriverInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Xml;
use WernerDweight\Stringy\Stringy;

class MetadataDriverManager
{
    /** @var string */
    private const DRIVER_SUFFIX = 'Driver';
    /** @var string */
    private const SIMPLIFIED_DRIVER_SUFFIX = 'Simplified';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DriverFactory */
    private $driverFactory;

    /** @var DoctrineCrudApiDriverInterface|null */
    private $driver;

    /**
     * DoctrineCrudApiMetadataFactory constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param DriverFactory          $driverFactory
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        DriverFactory $driverFactory
    ) {
        $this->entityManager = $entityManager;
        $this->driverFactory = $driverFactory;
    }

    /**
     * @param Stringy $shortDriverName
     *
     * @return Stringy
     */
    private function getRegularDriverName(Stringy $shortDriverName): Stringy
    {
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
        return $shortDriverName;
    }

    /**
     * @param MappingDriver $mappingDriver
     * @param Stringy       $shortDriverName
     *
     * @return DoctrineCrudApiDriverInterface
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getRegularDriver(
        MappingDriver $mappingDriver,
        Stringy $shortDriverName
    ): DoctrineCrudApiDriverInterface {
        $driver = $this->driverFactory->get((string)($this->getRegularDriverName($shortDriverName)));
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

        throw new MetadataFactoryException(
            MetadataFactoryException::UNEXPECTED_DRIVER,
            [get_class($driver)]
        );
    }

    /**
     * @param MappingDriverChain $mappingDriver
     *
     * @return Chain
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getChainDriver(MappingDriverChain $mappingDriver): Chain
    {
        $driver = $this->driverFactory->get(Chain::NAME);
        foreach ($mappingDriver->getDrivers() as $namespace => $childDriver) {
            $driver->addDriver($this->getCustomDriver($childDriver), $namespace);
        }
        if (null !== $mappingDriver->getDefaultDriver()) {
            $driver->setDefaultDriver($this->getCustomDriver($mappingDriver->getDefaultDriver()));
        }
        return $driver;
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
            return $this->getChainDriver($mappingDriver);
        }

        return $this->getRegularDriver($mappingDriver, $shortDriverName);
    }

    /**
     * @return DoctrineCrudApiDriverInterface
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getDriver(): DoctrineCrudApiDriverInterface
    {
        if (null === $this->driver) {
            $implementation = $this->entityManager->getConfiguration()->getMetadataDriverImpl();
            if (null === $implementation) {
                throw new MetadataFactoryException(
                    MetadataFactoryException::UNKNOWN_DEFAULT_DRIVER_IMPLEMENTATION
                );
            }
            $this->driver = $this->getCustomDriver($implementation);
        }
        return $this->driver;
    }
}
