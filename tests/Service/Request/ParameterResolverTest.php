<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Tests\DoctrineMetadataKernelTestCase;
use WernerDweight\RA\RA;

class ParameterResolverTest extends DoctrineMetadataKernelTestCase
{
    public function testResolveCreate(): void
    {
        $this->prepareRequest(['id', 'title'], [
            'title' => 'Test Title',
        ]);
        $container = static::getContainer();
        /** @var ParameterResolver $parameterResolver */
        $parameterResolver = $container->get(ParameterResolver::class);
        $parameterResolver->resolveCreate();
        $this->assertEquals('Article', (string)$parameterResolver->getStringy(ParameterEnum::ENTITY_NAME));
        $this->assertEquals([
            'article' => [
                'id' => true,
                'title' => true,
            ],
        ], $parameterResolver->getRA(ParameterEnum::RESPONSE_STRUCTURE)->toArray(RA::RECURSIVE));
        $this->assertEquals([
            'title' => 'Test Title',
        ], $parameterResolver->getRA(ParameterEnum::FIELDS)->toArray(RA::RECURSIVE));
    }

    private function prepareRequest(?array $responseStructure, ?array $fields): void
    {
        $container = static::getContainer();
        /** @var RequestStack $requestStack */
        $requestStack = $container->get(RequestStack::class);
        $requestStack->push(
            new Request(
                [],
                [],
                [
                    'entityName' => 'article',
                ],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                ],
                json_encode([
                    'responseStructure' => array_fill_keys($responseStructure, true),
                    'fields' => $fields,
                ])
            )
        );
    }
}
