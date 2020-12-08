<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use WernerDweight\DoctrineCrudApiBundle\Tests\Entity\Category;

class CategoryFixtures
{
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
        return new Category(
            1,
            'Movies',
            ArticleFixtures::createCollectionOfArticles()
        );
    }
}
