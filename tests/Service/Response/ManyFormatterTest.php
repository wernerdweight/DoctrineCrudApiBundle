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
     * @param RA      $expected
     * @param RA      $items
     * @param RA|null $responseStructure
     * @param string  $prefix
     *
     * @dataProvider provideValues
     */
    public function testFormat(
        RA $expected,
        RA $items,
        ?RA $responseStructure,
        string $prefix
    ): void {
        $formatter = self::$container->get(ManyFormatter::class);
        $value = $formatter->format($items, $responseStructure, $prefix);
        $this->assertEquals($expected, $value);
    }

    /**
     * @return array
     */
    public function provideValues(): array
    {
        return [
            [
                new RA(),
                new RA(ArticleFixtures::createCollectionOfArticles()->toArray(), RA::RECURSIVE),
                DoctrineCrudApiResponseStructureFixtures::createArticleResponseStructure(),
                'article',
            ],
        ];
    }
}
