<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Response;

use WernerDweight\DoctrineCrudApiBundle\Service\Response\ManyFormatter;
use WernerDweight\DoctrineCrudApiBundle\Tests\DoctrineMetadataKernelTestCase;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\ArticleFixtures;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\DoctrineCrudApiResponseStructureFixtures;
use WernerDweight\RA\RA;

class ManyFormatterTest extends DoctrineMetadataKernelTestCase
{
    /**
     * @dataProvider provideValues
     */
    public function testFormat(
        RA $expected,
        RA $items,
        ?RA $responseStructure,
        string $prefix
    ): void {
        /** @var ManyFormatter $formatter */
        $formatter = self::$container->get(ManyFormatter::class);
        $value = $formatter->format($items, $responseStructure, $prefix);
        $this->assertEquals($expected, $value);
    }

    /**
     * @return mixed[]
     */
    public function provideValues(): array
    {
        return [
            [
                new RA([
                    [
                        'id' => 1,
                        'title' => 'How I Learned to Stop Worrying and Love the Bomb',
                        'author' => [
                            'name' => 'Jules Winnfield',
                        ],
                    ],
                    [
                        'id' => 2,
                        'title' => 'Reservoir Dogs - behind the scenes',
                        'author' => null,
                    ],
                    [
                        'id' => 3,
                        'title' => 'Coherence',
                        'author' => [
                            'name' => 'Jules Winnfield',
                        ],
                    ],
                ], RA::RECURSIVE),
                new RA(ArticleFixtures::createCollectionOfArticles()->toArray(), RA::RECURSIVE),
                DoctrineCrudApiResponseStructureFixtures::createArticleResponseStructure(),
                'article.',
            ],
        ];
    }
}
