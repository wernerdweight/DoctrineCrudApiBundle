<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Doctrine\Bundle\DoctrineBundle\Mapping\MappingDriver as DoctrineBundleMappingDriver;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use WernerDweight\DoctrineCrudApiBundle\Exception\MetadataFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Chain;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\DoctrineCrudApiDriverInterface;
use WernerDweight\Stringy\Stringy;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MetadataDriverFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DriverFactory
     */
    private $driverFactory;

    /**
     * @var RegularDriverFactory
     */
    private $regularDriverFactory;

    /**
     * @var DoctrineCrudApiDriverInterface|null
     */
    private $driver;

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
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getDriver(): DoctrineCrudApiDriverInterface
    {
        if (null === $this->driver) {
            $implementation = $this->entityManager->getConfiguration()
                ->getMetadataDriverImpl();
            if (null === $implementation) {
                throw new MetadataFactoryException(MetadataFactoryException::UNKNOWN_DEFAULT_DRIVER_IMPLEMENTATION);
            }
            $this->driver = $this->getCustomDriver($implementation);
        }
        return $this->driver;
    }

    /**
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
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getCustomDriver(MappingDriver $mappingDriver): DoctrineCrudApiDriverInterface
    {
        $shortDriverName = new Stringy(get_class($mappingDriver));
        $shortDriverName = $shortDriverName->substring($shortDriverName->getPositionOfLastSubstring('\\') + 1);

        if ($mappingDriver instanceof DoctrineBundleMappingDriver) {
            $mappingDriver = $mappingDriver->getDriver();
        }
        if ($mappingDriver instanceof MappingDriverChain) {
            return $this->getChainDriver($mappingDriver);
        }

        return $this->regularDriverFactory->getRegularDriver($mappingDriver, $shortDriverName);
    }
}
