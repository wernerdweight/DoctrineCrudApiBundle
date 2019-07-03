<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;

class DoctrineCrudApiMetadataFixtures
{
    /**
     * @return DoctrineCrudApiMetadata
     */
    public static function createEmptyMetadata(): DoctrineCrudApiMetadata
    {
        return new DoctrineCrudApiMetadata(
            'App\\Entity\\Article',
            new ClassMetadata('App\\Entity\\Article'),
            DoctrineCrudApiMappingRAFixtures::createEmptyMappingRA()
        );
    }
}
