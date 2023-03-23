<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use WernerDweight\DoctrineCrudApiBundle\Exception\MetadataFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Annotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Attribute;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\DoctrineCrudApiDriverInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\Xml;
use WernerDweight\Stringy\Stringy;

class RegularDriverFactory
{
    /**
     * @var string
     */
    private const DRIVER_SUFFIX = 'Driver';

    /**
     * @var string
     */
    private const SIMPLIFIED_DRIVER_SUFFIX = 'Simplified';

    /**
     * @var DriverFactory
     */
    private $driverFactory;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->driverFactory = $driverFactory;
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getRegularDriver(
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

        if ($driver instanceof Attribute) {
            // TODO: this driver is not currently supported
            return $driver;
        }

        throw new MetadataFactoryException(MetadataFactoryException::UNEXPECTED_DRIVER, [get_class($driver)]);
    }

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
}
