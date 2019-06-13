<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;

abstract class AbstractDriver
{
    /**
     * @var MappingDriver
     */
    protected $originalDriver;

    /**
     * @param MappingDriver $driver
     * @return DoctrineCrudApiDriverInterface
     */
    public function setOriginalDriver(MappingDriver $driver): DoctrineCrudApiDriverInterface
    {
        $this->originalDriver = $driver;
        return $this;
    }
}
