<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterValidator;
use WernerDweight\DoctrineCrudApiBundle\Tests\DoctrineMetadataKernelTestCase;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\DoctrineCrudApiResponseStructureFixtures;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ParameterValidatorTest extends DoctrineMetadataKernelTestCase
{
    /**
     * @param mixed[]|null $filter
     *
     * @dataProvider provideFilterValues
     */
    public function testValidateFilter(RA $expected, ?array $filter): void
    {
        $this->prepareRequest();
        $container = static::getContainer();
        /** @var ParameterValidator $parameterValidator */
        $parameterValidator = $container->get(ParameterValidator::class);
        $value = $parameterValidator->validateFilter($filter);
        $this->assertEquals($expected, $value);
    }

    /**
     * @param string[]|null $orderBy
     *
     * @dataProvider provideOrderByValues
     */
    public function testValidateOrderBy(RA $expected, ?array $orderBy): void
    {
        $this->prepareRequest();
        $container = static::getContainer();
        /** @var ParameterValidator $parameterValidator */
        $parameterValidator = $container->get(ParameterValidator::class);
        $value = $parameterValidator->validateOrderBy($orderBy);
        $this->assertEquals($expected, $value);
    }

    /**
     * @param string[]|null $groupBy
     *
     * @dataProvider provideGroupByValues
     */
    public function testValidateGroupBy(?RA $expected, ?array $groupBy): void
    {
        $this->prepareRequest();
        $container = static::getContainer();
        /** @var ParameterValidator $parameterValidator */
        $parameterValidator = $container->get(ParameterValidator::class);
        $value = $parameterValidator->validateGroupBy($groupBy);
        $this->assertEquals($expected, $value);
    }

    /**
     * @param mixed[]|null $responseStructure
     *
     * @dataProvider provideResponseStructureValues
     */
    public function testValidateResponseStructure(?RA $expected, ?array $responseStructure, Stringy $entityName): void
    {
        $this->prepareRequest();
        $container = static::getContainer();
        /** @var ParameterValidator $parameterValidator */
        $parameterValidator = $container->get(ParameterValidator::class);
        $value = $parameterValidator->validateResponseStructure($responseStructure, $entityName);
        $this->assertEquals($expected, $value);
    }

    /**
     * @param string[]|null $fields
     *
     * @dataProvider provideFieldsValues
     */
    public function testValidateFields(RA $expected, ?array $fields): void
    {
        $this->prepareRequest();
        $container = static::getContainer();
        /** @var ParameterValidator $parameterValidator */
        $parameterValidator = $container->get(ParameterValidator::class);
        $value = $parameterValidator->validateFields($fields);
        $this->assertEquals($expected, $value);
    }

    /**
     * @return mixed[]
     */
    public static function provideFilterValues(): array
    {
        return [
            [new RA(), null],
            [new RA(), []],
            [
                new RA(), [
                    'unexpected' => 'value',
                ]],
            [
                new RA(),
                [
                    ParameterEnum::FILTER_LOGIC => ParameterEnum::FILTER_LOGIC_OR,
                ],
            ],
            [
                new RA([
                    'logic' => 'and',
                    'conditions' => [
                        [
                            'field' => new Stringy('author.name'),
                            'operator' => ParameterEnum::FILTER_OPERATOR_BEGINS_WITH,
                            'value' => 'Jules%',
                        ],
                    ],
                ], RA::RECURSIVE),
                [
                    ParameterEnum::FILTER_CONDITIONS => [
                        [
                            ParameterEnum::FILTER_FIELD => 'this.author.name',
                            ParameterEnum::FILTER_OPERATOR => ParameterEnum::FILTER_OPERATOR_BEGINS_WITH,
                            ParameterEnum::FILTER_VALUE => 'Jules',
                        ],
                    ],
                ],
            ],
            [
                new RA([
                    'logic' => 'or',
                    'conditions' => [
                        [
                            'field' => new Stringy('author.name'),
                            'operator' => ParameterEnum::FILTER_OPERATOR_BEGINS_WITH,
                            'value' => 'Jules%',
                        ],
                    ],
                ], RA::RECURSIVE),
                [
                    ParameterEnum::FILTER_LOGIC => ParameterEnum::FILTER_LOGIC_OR,
                    ParameterEnum::FILTER_CONDITIONS => [
                        [
                            ParameterEnum::FILTER_FIELD => 'this.author.name',
                            ParameterEnum::FILTER_OPERATOR => ParameterEnum::FILTER_OPERATOR_BEGINS_WITH,
                            ParameterEnum::FILTER_VALUE => 'Jules',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function provideOrderByValues(): array
    {
        return [
            [new RA(), null],
            [new RA(), []],
            [
                new RA([
                    'orderBy' => [
                        'field' => 'this.author.name',
                        'direction' => 'desc',
                    ],
                ], RA::RECURSIVE),
                [
                    ParameterEnum::ORDER_BY => [
                        ParameterEnum::ORDER_BY_FIELD => 'this.author.name',
                        ParameterEnum::ORDER_BY_DIRECTION => ParameterEnum::ORDER_BY_DIRECTION_DESC,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function provideGroupByValues(): array
    {
        return [
            [null, null],
            [new RA(), []],
            [
                new RA([
                    'groupBy' => [
                        'field' => new Stringy('this.author.name'),
                        'direction' => 'desc',
                        'aggregates' => [],
                    ],
                ], RA::RECURSIVE),
                [
                    ParameterEnum::GROUP_BY => [
                        ParameterEnum::GROUP_BY_FIELD => 'this.author.name',
                        ParameterEnum::GROUP_BY_DIRECTION => ParameterEnum::GROUP_BY_DIRECTION_DESC,
                    ],
                ],
            ],
            [
                new RA([
                    'groupBy' => [
                        'field' => new Stringy('this.author.name'),
                        'direction' => 'desc',
                        'aggregates' => [
                            [
                                'field' => 'this.author.id',
                                'function' => 'count',
                            ],
                        ],
                    ],
                ], RA::RECURSIVE),
                [
                    ParameterEnum::GROUP_BY => [
                        ParameterEnum::GROUP_BY_FIELD => 'this.author.name',
                        ParameterEnum::GROUP_BY_DIRECTION => ParameterEnum::GROUP_BY_DIRECTION_DESC,
                        ParameterEnum::GROUP_BY_AGGREGATES => [
                            [
                                ParameterEnum::GROUP_BY_AGGREGATE_FIELD => 'this.author.id',
                                ParameterEnum::GROUP_BY_AGGREGATE_FUNCTION => ParameterEnum::GROUP_BY_AGGREGATE_FUNCTION_COUNT,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function provideResponseStructureValues(): array
    {
        return [
            [null, null, new Stringy('')],
            [
                new RA([
                    'article' => [],
                ], RA::RECURSIVE),
                [],
                new Stringy('article'),
            ],
            [
                DoctrineCrudApiResponseStructureFixtures::createArticleResponseStructure(),
                [
                    'id' => 'true',
                    'title' => 'true',
                    'author' => [
                        'name' => 'true',
                    ],
                ],
                new Stringy('article'),
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function provideFieldsValues(): array
    {
        return [
            [new RA(), null],
            [new RA([]), []],
            [
                new RA([
                    'title' => 'Some Article',
                    'author' => [
                        'name' => 'Johannes Brahms',
                    ],
                ], RA::RECURSIVE),
                [
                    'title' => 'Some Article',
                    'author' => [
                        'name' => 'Johannes Brahms',
                    ],
                ],
            ],
        ];
    }

    private function prepareRequest(string $prefix = 'article'): void
    {
        $container = static::getContainer();
        /** @var RequestStack $requestStack */
        $requestStack = $container->get(RequestStack::class);
        $requestStack->push(
            new Request(
                [],
                [],
                [
                    'entityName' => $prefix,
                ]
            )
        );
    }
}
