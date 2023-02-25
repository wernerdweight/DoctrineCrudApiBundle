<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\Formatter;
use WernerDweight\DoctrineCrudApiBundle\Tests\DoctrineMetadataKernelTestCase;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\ArticleFixtures;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\DoctrineCrudApiResponseStructureFixtures;
use WernerDweight\RA\RA;

class FormatterTest extends DoctrineMetadataKernelTestCase
{
    /**
     * @dataProvider provideValues
     */
    public function testFormat(
        RA $expected,
        ApiEntityInterface $item,
        ?RA $responseStructure,
        string $prefix
    ): void {
        $this->prepareRequest($responseStructure, $prefix);
        $container = static::getContainer();
        /** @var Formatter $formatter */
        $formatter = $container->get(Formatter::class);
        $value = $formatter->format($item, $responseStructure, $prefix);
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
                    'id' => 1,
                    'title' => 'How I Learned to Stop Worrying and Love the Bomb',
                    'author' => [
                        'name' => 'Jules Winnfield',
                    ],
                ], RA::RECURSIVE),
                ArticleFixtures::createArticle(),
                DoctrineCrudApiResponseStructureFixtures::createArticleResponseStructure(),
                'article.',
            ],
            [
                new RA([
                    'id' => 2,
                    'title' => 'Reservoir Dogs - behind the scenes',
                    'author' => null,
                ], RA::RECURSIVE),
                ArticleFixtures::createArticleWithoutAuthor(),
                DoctrineCrudApiResponseStructureFixtures::createArticleResponseStructure(),
                'article.',
            ],
        ];
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function prepareRequest(?RA $responseStructure, string $prefix): void
    {
        $container = static::getContainer();
        /** @var RequestStack $requestStack */
        $requestStack = $container->get(RequestStack::class);
        $requestStack->push(
            new Request(
                [
                    'responseStructure' => $responseStructure?->getRA('article')
                        ->toArray(RA::RECURSIVE),
                ],
                [],
                [
                    'entityName' => trim($prefix, '.'),
                ]
            )
        );

        /** @var ParameterResolver $parameterResolver */
        $parameterResolver = $container->get(ParameterResolver::class);
        $parameterResolver->resolveList();
    }
}
