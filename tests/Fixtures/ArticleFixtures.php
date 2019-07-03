<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
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

    public static function createArticleWithoutAuthor(): Article
    {

    }

    public static function createCollectionOfArticles(): ArrayCollection
    {

    }
}
