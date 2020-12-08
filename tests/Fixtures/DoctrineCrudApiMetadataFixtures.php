<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Tests\Entity\Article;

class DoctrineCrudApiMetadataFixtures
{
    public static function createEmptyMetadata(): DoctrineCrudApiMetadata
    {
        return new DoctrineCrudApiMetadata(
            Article::class,
            new ClassMetadata(Article::class),
            DoctrineCrudApiMappingRAFixtures::createEmptyMapping()
        );
    }

    public static function createArticleMetadata(): DoctrineCrudApiMetadata
    {
        return new DoctrineCrudApiMetadata(
            Article::class,
            new ClassMetadata(Article::class),
            DoctrineCrudApiMappingRAFixtures::createArticleMapping()
        );
    }
}
