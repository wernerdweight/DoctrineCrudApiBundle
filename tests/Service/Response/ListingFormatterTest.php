<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\ListingFormatter;
use WernerDweight\DoctrineCrudApiBundle\Tests\DoctrineMetadataKernelTestCase;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\ArticleFixtures;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\DoctrineCrudApiResponseStructureFixtures;
use WernerDweight\RA\RA;

class ListingFormatterTest extends DoctrineMetadataKernelTestCase
{
    /**
     * @dataProvider provideValues
     */
    public function testFormatListing(
        RA $expected,
        RA $items,
        ?RA $groupBy,
        ?RA $responseStructure
    ): void {
        $this->prepareRequest($groupBy, $responseStructure);
        $container = static::getContainer();
        /** @var ListingFormatter $formatter */
        $formatter = $container->get(ListingFormatter::class);
        $value = $formatter->formatListing($items);
        $this->assertEquals($expected, $value);
    }

    /**
     * @return mixed[]
     */
    public static function provideValues(): array
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
                null,
                DoctrineCrudApiResponseStructureFixtures::createArticleResponseStructure(),
            ],
            [
                new RA([
                    [
                        ParameterEnum::GROUP_BY_AGGREGATES => [],
                        ParameterEnum::GROUP_BY_FIELD => 'author.name',
                        ParameterEnum::GROUP_BY_VALUE => 'Jules Winnfield',
                        ParameterEnum::GROUP_BY_HAS_GROUPS => false,
                        ParameterEnum::GROUP_BY_ITEMS => [
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
                        ],
                    ],
                ], RA::RECURSIVE),
                new RA([
                    [
                        'value' => 'Jules Winnfield',
                        'items' => ArticleFixtures::createCollectionOfArticles()->toArray(),
                    ],
                ], RA::RECURSIVE),
                new RA([[
                    'field' => 'author.name',
                    'direction' => 'asc',
                ]]),
                DoctrineCrudApiResponseStructureFixtures::createArticleResponseStructure(),
            ],
        ];
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function prepareRequest(?RA $groupBy, ?RA $responseStructure): void
    {
        $container = static::getContainer();
        /** @var RequestStack $requestStack */
        $requestStack = $container->get(RequestStack::class);
        $requestStack->push(
            new Request(
                [
                    'responseStructure' => $responseStructure?->getRA('article')
                        ->toArray(RA::RECURSIVE),
                    'groupBy' => $groupBy?->toArray(RA::RECURSIVE),
                ],
                [],
                [
                    'entityName' => 'article',
                ]
            )
        );

        /** @var ParameterResolver $parameterResolver */
        $parameterResolver = $container->get(ParameterResolver::class);
        $parameterResolver->resolveList();
    }
}
