<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DoctrineCrudApiExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        AnnotationRegistry::registerFile(__DIR__ . '/../Mapping/Annotation/Creatable.php');
        AnnotationRegistry::registerFile(__DIR__ . '/../Mapping/Annotation/Listable.php');
        AnnotationRegistry::registerFile(__DIR__ . '/../Mapping/Annotation/Updatable.php');
        AnnotationRegistry::registerFile(__DIR__ . '/../Mapping/Annotation/Metadata.php');
    }
}
