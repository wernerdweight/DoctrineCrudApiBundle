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
     * @param RA|null $responseStructure
     * @param string  $prefix
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function prepareRequest(?RA $responseStructure, string $prefix): void
    {
        /** @var RequestStack $requestStack */
        $requestStack = self::$container->get(RequestStack::class);
        $requestStack->push(
            new Request(
                [
                    'responseStructure' => null !== $responseStructure
                        ? $responseStructure->getRA('article')->toArray(RA::RECURSIVE)
                        : null,
                ],
                [],
                [
                    'entityName' => trim($prefix, '.'),
                ]
            )
        );

        /** @var ParameterResolver $parameterResolver */
        $parameterResolver = self::$container->get(ParameterResolver::class);
        $parameterResolver->resolveList();
    }

    /**
     * @param RA                 $expected
     * @param ApiEntityInterface $item
     * @param RA|null            $responseStructure
     * @param string             $prefix
     *
     * @dataProvider provideValues
     */
    public function testFormat(
        RA $expected,
        ApiEntityInterface $item,
        ?RA $responseStructure,
        string $prefix
    ): void {
        $this->prepareRequest($responseStructure, $prefix);
        /** @var Formatter $formatter */
        $formatter = self::$container->get(Formatter::class);
        $value = $formatter->format($item, $responseStructure, $prefix);
        $this->assertEquals($expected, $value);
    }

    /**
     * @return array
     */
    public function provideValues(): array
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
}
