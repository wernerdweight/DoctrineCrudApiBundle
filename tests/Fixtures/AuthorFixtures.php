<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Tests\Entity\Article;
use WernerDweight\DoctrineCrudApiBundle\Tests\Entity\Author;
use WernerDweight\DoctrineCrudApiBundle\Tests\Entity\Category;

class AuthorFixtures
{
    /**
     * @return Author
     */
    public static function createAuthor(): Author
    {
        return new Author(
            1,
            'Jules Winnfield',
            'jules@motherfucker.com'
        );
    }
}
