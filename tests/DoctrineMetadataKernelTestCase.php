<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DoctrineMetadataKernelTestCase extends KernelTestCase
{
    /**
     * Reads all mapping information for entities (incl. API config).
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->getMetadataFactory()
            ->getAllMetadata();
    }
}
