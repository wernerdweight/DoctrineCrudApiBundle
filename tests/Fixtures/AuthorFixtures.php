<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use WernerDweight\DoctrineCrudApiBundle\Tests\Entity\Author;

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
