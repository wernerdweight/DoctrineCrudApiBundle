<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Iterator;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use WernerDweight\DoctrineCrudApiBundle\Exception\DriverFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\DoctrineCrudApiDriverInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class DriverFactory
{
    /** @var RA */
    private $drivers;

    /**
     * DriverFactory constructor.
     *
     * @param RewindableGenerator<DoctrineCrudApiDriverInterface> $drivers
     *
     * @throws \Safe\Exceptions\MbstringException
     */
    public function __construct(RewindableGenerator $drivers)
    {
        $this->drivers = new RA();
        /** @var Iterator<DoctrineCrudApiDriverInterface> $iterator */
        $iterator = $drivers->getIterator();
        while ($iterator->valid()) {
            $driver = $iterator->current();
            $driverClassName = new Stringy(get_class($driver));
            $this->drivers->set(
                (string)($driverClassName->substring($driverClassName->getPositionOfLastSubstring('\\') + 1)),
                $driver
            );
            $iterator->next();
        }
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function get(string $driverType): DoctrineCrudApiDriverInterface
    {
        if (true !== $this->drivers->hasKey($driverType)) {
            throw new DriverFactoryException(DriverFactoryException::INVALID_DRIVER_TYPE, [$driverType]);
        }
        return $this->drivers->get($driverType);
    }
}
