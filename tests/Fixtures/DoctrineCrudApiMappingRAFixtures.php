<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;

class DoctrineCrudApiMappingRAFixtures
{
    /**
     * @return RA
     */
    public static function createEmptyMapping(): RA
    {
        return (new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::ACCESSIBLE, false)
            ->set(DoctrineCrudApiMappingTypeInterface::LISTABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::DEFAULT_LISTABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::CREATABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::CREATABLE_NESTED, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::UPDATABLE, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::UPDATABLE_NESTED, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::METADATA, new RA())
            ;
    }

    /**
     * @return RA
     */
    public static function createArticleMapping(): RA
    {
        return (new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::ACCESSIBLE, true)
            ->set(DoctrineCrudApiMappingTypeInterface::LISTABLE, new RA(['id', 'title', 'author', 'category']))
            ->set(DoctrineCrudApiMappingTypeInterface::DEFAULT_LISTABLE, new RA(['id', 'title']))
            ->set(DoctrineCrudApiMappingTypeInterface::CREATABLE, new RA(['title', 'author', 'category']))
            ->set(DoctrineCrudApiMappingTypeInterface::CREATABLE_NESTED, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::UPDATABLE, new RA(['title', 'author', 'category']))
            ->set(DoctrineCrudApiMappingTypeInterface::UPDATABLE_NESTED, new RA())
            ->set(DoctrineCrudApiMappingTypeInterface::METADATA, new RA())
            ;
    }
}
