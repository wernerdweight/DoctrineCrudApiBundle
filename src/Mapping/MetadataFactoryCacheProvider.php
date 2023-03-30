<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;

class MetadataFactoryCacheProvider
{
    /**
     * @var string
     */
    private const CACHE_NAMESPACE = 'DOCTRINE_CRUD_API_CLASSMETADATA';

    private ClassMetadataFactory $metadataFactory;

    private ?CacheItemPoolInterface $cacheItemPool = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->metadataFactory = $entityManager->getMetadataFactory();
    }

    public function store(ClassMetadata $metadata, ?DoctrineCrudApiMetadata $configuration): void
    {
        $cacheItemPool = $this->getCacheItemPool();
        $cacheKey = $this->getCacheKey($metadata->name);
        $item = $cacheItemPool->getItem($cacheKey);
        $item->set($configuration);
        $cacheItemPool->save($item);
    }

    public function recall(string $className): ?DoctrineCrudApiMetadata
    {
        $cacheItemPool = $this->getCacheItemPool();
        $cacheKey = $this->getCacheKey($className);
        $item = $cacheItemPool->getItem($cacheKey);
        if (true === $item->isHit()) {
            /** @var DoctrineCrudApiMetadata $configuration */
            $configuration = $item->get();
            return $configuration;
        }
        return null;
    }

    private function getCacheKey(string $name): string
    {
        return \Safe\sprintf('%s_$%s', str_replace('\\', '_', $name), self::CACHE_NAMESPACE);
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function getCacheItemPool(): CacheItemPoolInterface
    {
        if (null !== $this->cacheItemPool) {
            return $this->cacheItemPool;
        }

        $getCache = \Closure::bind(
            static function (AbstractClassMetadataFactory $metadataFactory): ?CacheItemPoolInterface {
                return $metadataFactory->getCache();
            },
            null,
            get_class($this->metadataFactory)
        );

        $metadataCache = $getCache($this->metadataFactory);

        if (null !== $metadataCache) {
            $this->cacheItemPool = $metadataCache;
            return $this->cacheItemPool;
        }

        $this->cacheItemPool = new ArrayAdapter();

        return $this->cacheItemPool;
    }
}
