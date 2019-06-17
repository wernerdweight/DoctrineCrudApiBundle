<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use WernerDweight\DoctrineCrudApiBundle\Exception\DoctrineCrudApiDriverFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\DoctrineCrudApiDriverInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class DoctrineCrudApiDriverFactory
{
    /** @var RA */
    private $drivers;

    /**
     * DoctrineCrudApiDriverFactory constructor.
     *
     * @param RewindableGenerator $drivers
     */
    public function __construct(RewindableGenerator $drivers)
    {
        $this->drivers = new RA();
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
     * @param string $driverType
     *
     * @return DoctrineCrudApiDriverInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function get(string $driverType): DoctrineCrudApiDriverInterface
    {
        if (true !== $this->drivers->hasKey($driverType)) {
            throw new DoctrineCrudApiDriverFactoryException(
                DoctrineCrudApiDriverFactoryException::INVALID_DRIVER_TYPE,
                [$driverType]
            );
        }
        return $this->drivers->get($driverType);
    }
}
