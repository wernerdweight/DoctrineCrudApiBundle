<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\Exception\ChainDriverException;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class Chain extends AbstractDriver implements DoctrineCrudApiDriverInterface
{
    /** @var string */
    public const NAME = 'Chain';

    /** @var DoctrineCrudApiDriverInterface|null */
    private $defaultDriver;

    /** @var RA */
    private $drivers;

    /**
     * Chain constructor.
     */
    public function __construct()
    {
        $this->drivers = new RA();
    }

    /**
     * @param FileLocator $locator
     *
     * @return Chain
     */
    public function setLocator(FileLocator $locator): DoctrineCrudApiDriverInterface
    {
        throw new ChainDriverException(ChainDriverException::NO_LOCATOR_NEEDED);
    }

    /**
     * @param AnnotationReader $reader
     *
     * @return Chain
     */
    public function setAnnotationReader(AnnotationReader $reader): DoctrineCrudApiDriverInterface
    {
        throw new ChainDriverException(ChainDriverException::NO_READER_NEEDED);
    }

    /**
     * @param ClassMetadata $metadata
     * @param RA            $config
     *
     * @return RA
     */
    public function readMetadata(ClassMetadata $metadata, RA $config): RA
    {
        /** @var DoctrineCrudApiDriverInterface $driver */
        foreach ($this->drivers as $namespace => $driver) {
            if (0 === (new Stringy($metadata->name))->getPositionOfSubstring($namespace)) {
                return $driver->readMetadata($metadata, $config);
            }
        }

        if (null !== $this->defaultDriver) {
            return $this->defaultDriver->readMetadata($metadata, $config);
        }

        throw new ChainDriverException(ChainDriverException::NO_DRIVER_FOR_ENTITY, [$metadata->name]);
    }

    /**
     * @param DoctrineCrudApiDriverInterface $driver
     * @param string                         $namespace
     *
     * @return Chain
     */
    public function addDriver(DoctrineCrudApiDriverInterface $driver, string $namespace): self
    {
        $this->drivers->set($namespace, $driver);
        return $this;
    }

    /**
     * @return RA
     */
    public function getDrivers(): RA
    {
        return $this->drivers;
    }

    /**
     * @return DoctrineCrudApiDriverInterface|null
     */
    public function getDefaultDriver(): ?DoctrineCrudApiDriverInterface
    {
        return $this->defaultDriver;
    }

    /**
     * @param DoctrineCrudApiDriverInterface $driver
     *
     * @return Chain
     */
    public function setDefaultDriver(DoctrineCrudApiDriverInterface $driver): self
    {
        $this->defaultDriver = $driver;
        return $this;
    }
}
