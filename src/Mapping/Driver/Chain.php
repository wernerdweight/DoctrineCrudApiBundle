<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
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
     * @return Chain
     */
    public function setLocator(FileLocator $locator): DoctrineCrudApiDriverInterface
    {
        throw new ChainDriverException(ChainDriverException::NO_LOCATOR_NEEDED);
    }

    /**
     * @return Chain
     */
    public function setAnnotationReader(AnnotationReader $reader): DoctrineCrudApiDriverInterface
    {
        throw new ChainDriverException(ChainDriverException::NO_READER_NEEDED);
    }

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
     * @return Chain
     */
    public function addDriver(DoctrineCrudApiDriverInterface $driver, string $namespace): self
    {
        $this->drivers->set($namespace, $driver);
        return $this;
    }

    public function getDrivers(): RA
    {
        return $this->drivers;
    }

    public function getDefaultDriver(): ?DoctrineCrudApiDriverInterface
    {
        return $this->defaultDriver;
    }

    /**
     * @return Chain
     */
    public function setDefaultDriver(DoctrineCrudApiDriverInterface $driver): self
    {
        $this->defaultDriver = $driver;
        return $this;
    }
}
