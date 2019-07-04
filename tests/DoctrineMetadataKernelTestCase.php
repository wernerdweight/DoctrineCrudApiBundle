<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineMetadataKernelTestCase extends KernelTestCase
{
    /**
     * Reads all mapping information for entities (incl. API config).
     */
    protected function setUp(): void
    {
        self::bootKernel();
        /** @var EntityManager $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $entityManager->getMetadataFactory()->getAllMetadata();
    }
}
