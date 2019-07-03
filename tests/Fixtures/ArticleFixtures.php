<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use WernerDweight\DoctrineCrudApiBundle\Tests\Entity\Article;

class ArticleFixtures
{
    /**
     * @return Article
     */
    public static function createArticle(): Article
    {
        return new Article(
            1,
            'How I Learned to Stop Worrying and Love the Bomb',
            AuthorFixtures::createAuthor(),
            CategoryFixtures::createEmptyCategory()
        );
    }

    /**
     * @return Article
     */
    public static function createArticleWithoutAuthor(): Article
    {
        return new Article(
            2,
            'Reservoir Dogs - behind the scenes',
            null,
            CategoryFixtures::createEmptyCategory()
        );
    }

    /**
     * @return Article
     */
    public static function createArticleWithoutCategory(): Article
    {
        return new Article(
            2,
            'Reservoir Dogs - behind the scenes',
            AuthorFixtures::createAuthor(),
            null
        );
    }

    /**
     * @return ArrayCollection
     */
    public static function createCollectionOfArticles(): ArrayCollection
    {
        return new ArrayCollection([
            self::createArticle(),
            self::createArticleWithoutAuthor(),
            self::createArticleWithoutCategory(),
        ]);
    }
}
