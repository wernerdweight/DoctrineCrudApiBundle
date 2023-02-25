<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\DoctrineCrudApiBundle\Tests\DoctrineMetadataKernelTestCase;

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
        $this->assertEquals('article', $parameterResolver->getParameter(ParameterEnum::ENTITY_NAME));
        dump($parameterResolver->getParameter(ParameterEnum::RESPONSE_STRUCTURE));
        dump($parameterResolver->getParameter(ParameterEnum::FIELDS));
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
                [],
                json_encode([
                    'responseStructure' => $responseStructure,
                    'fields' => $fields,
                ])
            )
        );
    }
}