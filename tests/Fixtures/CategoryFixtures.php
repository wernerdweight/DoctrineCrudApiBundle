<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Tests\Entity\Article;
use WernerDweight\DoctrineCrudApiBundle\Tests\Entity\Category;

class CategoryFixtures
{
    /**
     * @return Category
     */
    public static function createEmptyCategory(): Category
    {
        return new Category(
            1,
            'Movies',
            new ArrayCollection()
        );
    }

    public static function createCategoryWithArticles(): Category
    {

    }
}
