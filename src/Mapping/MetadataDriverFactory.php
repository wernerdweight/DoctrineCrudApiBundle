<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManagerInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\MetadataFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Chain;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\DoctrineCrudApiDriverInterface;
use WernerDweight\Stringy\Stringy;

class MetadataDriverFactory
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DriverFactory */
    private $driverFactory;

    /** @var RegularDriverFactory */
    private $regularDriverFactory;

    /** @var DoctrineCrudApiDriverInterface|null */
    private $driver;

    /**
     * MetadataDriverFactory constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param DriverFactory          $driverFactory
     * @param RegularDriverFactory   $regularDriverFactory
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        DriverFactory $driverFactory,
        RegularDriverFactory $regularDriverFactory
    ) {
        $this->entityManager = $entityManager;
        $this->driverFactory = $driverFactory;
        $this->regularDriverFactory = $regularDriverFactory;
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
        /** @var Chain $driver */
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

        return $this->regularDriverFactory->getRegularDriver($mappingDriver, $shortDriverName);
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
