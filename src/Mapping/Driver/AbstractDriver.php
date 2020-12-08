<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Persistence\Mapping\Driver\MappingDriver;

abstract class AbstractDriver implements DoctrineCrudApiDriverInterface
{
    /**
     * @var MappingDriver
     */
    protected $originalDriver;

    public function setOriginalDriver(MappingDriver $driver): DoctrineCrudApiDriverInterface
    {
        $this->originalDriver = $driver;
        return $this;
    }
}
