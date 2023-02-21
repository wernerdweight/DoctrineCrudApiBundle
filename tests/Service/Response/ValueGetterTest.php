<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Response;

use PHPUnit\Framework\TestCase;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\ValueGetter;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\ArticleFixtures;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\AuthorFixtures;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\CategoryFixtures;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\DoctrineCrudApiMetadataFixtures;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ValueGetterTest extends TestCase
{
    /**
     * @param mixed   $expected
     * @param mixed[] $args
     *
     * @dataProvider provideEntities
     */
    public function testGetEntityPropertyValue(
        $expected,
        ApiEntityInterface $entity,
        Stringy $field,
        array $args = []
    ): void {
        $valueGetter = new ValueGetter();
        $value = $valueGetter->getEntityPropertyValue($entity, $field, $args);
        $this->assertEquals($expected, $value);
    }

    /**
     * @throws \Safe\Exceptions\StringsException
     *
     * @dataProvider provideRelatedEntities
     */
    public function testGetRelatedEntityValue(
        ?ApiEntityInterface $expected,
        ApiEntityInterface $entity,
        Stringy $field
    ): void {
        $valueGetter = new ValueGetter();
        $value = $valueGetter->getRelatedEntityValue($entity, $field);
        $this->assertEquals($expected, $value);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     *
     * @dataProvider provideRelatedCollections
     */
    public function testGetRelatedCollectionValue(
        RA $expected,
        ApiEntityInterface $entity,
        Stringy $field,
        DoctrineCrudApiMetadata $metadata
    ): void {
        $valueGetter = new ValueGetter();
        $value = $valueGetter->getRelatedCollectionValue($entity, $field, $metadata);
        $this->assertEquals($expected, $value);
    }

    /**
     * @return mixed[]
     */
    public function provideEntities(): array
    {
        return [
            [
                'kitten123',
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function getKitten(string $suffix): string
                    {
                        return 'kitten' . $suffix;
                    }
                },
                new Stringy('kitten'),
                ['123'],
            ],
            [
                'kitten',
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function getKitten(): string
                    {
                        return 'kitten';
                    }
                },
                new Stringy('kitten'),
            ],
            [
                true,
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function isKitten(): bool
                    {
                        return true;
                    }
                },
                new Stringy('kitten'),
            ],
            [
                'kitten',
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function kitten(): string
                    {
                        return 'kitten';
                    }
                },
                new Stringy('kitten'),
            ],
            [
                'kitten',
                new class() implements ApiEntityInterface {
                    /**
                     * @var string string
                     */
                    public $kitten = 'kitten';

                    public function getId(): int
                    {
                        return 1;
                    }
                },
                new Stringy('kitten'),
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    public function provideRelatedEntities(): array
    {
        return [
            [
                AuthorFixtures::createAuthor(),
                ArticleFixtures::createArticle(),
                new Stringy('author'),
            ],
            [
                null,
                ArticleFixtures::createArticleWithoutAuthor(),
                new Stringy('author'),
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    public function provideRelatedCollections(): array
    {
        return [
            [
                new RA(ArticleFixtures::createCollectionOfArticles()->toArray()),
                CategoryFixtures::createCategoryWithArticles(),
                new Stringy('articles'),
                DoctrineCrudApiMetadataFixtures::createEmptyMetadata(),
            ],
            [
                new RA(),
                CategoryFixtures::createEmptyCategory(),
                new Stringy('articles'),
                DoctrineCrudApiMetadataFixtures::createEmptyMetadata(),
            ],
        ];
    }
}
