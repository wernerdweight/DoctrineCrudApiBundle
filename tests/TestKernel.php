<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DbalSchemaFilterPass;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\EntityListenerPass;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\DoctrineValidationPass;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use WernerDweight\DoctrineCrudApiBundle\DoctrineCrudApiBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @return BundleInterface[]
     */
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DoctrineCrudApiBundle(),
        ];
    }

    /**
     * @param RouteCollectionBuilder $routes
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
    }

    /**
     * @param ContainerBuilder $builder
     * @param LoaderInterface  $loader
     */
    protected function configureContainer(ContainerBuilder $builder, LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/../vendor/symfony/framework-bundle/Resources/config/test.xml');
        $loader->load(__DIR__ . '/../vendor/doctrine/doctrine-bundle/Resources/config/dbal.xml');
        $loader->load(__DIR__ . '/../vendor/doctrine/doctrine-bundle/Resources/config/orm.xml');
        $loader->load(__DIR__ . '/../src/Resources/config/services.yaml');
        $builder->loadFromExtension('framework', [
            'secret' => 'not-so-secret',
            'test' => true,
        ]);
        $builder->loadFromExtension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_pgsql',
                'server_version' => '11.0',
                'charset' => 'utf8',
                'default_table_options' => [
                    'charset' => 'utf8',
                    'collate' => 'utf8_unicode_ci',
                ],
                'memory' => true,
                'types' => [],
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'proxy_namespace' => 'Proxies',
                'proxy_dir' => __DIR__ . '/cache/doctrine/orm/Proxies',
                'entity_managers' => [
                    'default' => [
                        'mappings' => [
                            'Test' => [
                                'is_bundle' => false,
                                'type' => 'annotation',
                                'dir' => __DIR__ . '/Entity',
                                'prefix' => 'WernerDweight\DoctrineCrudApiBundle\Tests\Entity',
                                'alias' => 'Test',
                                'mapping' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $builder->addCompilerPass(
            new RegisterEventListenersAndSubscribersPass(
                'doctrine.connections',
                'doctrine.dbal.%s_connection.event_manager',
                'doctrine'
            ),
            PassConfig::TYPE_BEFORE_OPTIMIZATION
        );
        $builder->addCompilerPass(new DoctrineValidationPass('orm'));
        $builder->addCompilerPass(new EntityListenerPass());
        $builder->addCompilerPass(new ServiceRepositoryCompilerPass());
        $builder->addCompilerPass(new DbalSchemaFilterPass());

        //$builder->autowire('test.private_services_locator', ServiceLocator::class);
        //$builder->addCompilerPass(new TestServiceContainerWeakRefPass(), PassConfig::TYPE_BEFORE_REMOVING, -32);
        //$builder->addCompilerPass(new TestServiceContainerRealRefPass(), PassConfig::TYPE_AFTER_REMOVING);
        //$builder->autowire('test.service_container', TestContainer::class);
        //$builder->autowire(EntityManagerInterface::class, EntityManagerInterface::class);
    }
}
