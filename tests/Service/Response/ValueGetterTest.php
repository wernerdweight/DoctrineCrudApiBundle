<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Response;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\ValueGetter;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\DoctrineCrudApiMetadataFixtures;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ValueGetterTest extends TestCase
{
    /**
     * @param mixed              $expected
     * @param ApiEntityInterface $entity
     * @param Stringy            $field
     * @param array              $args
     *
     * @dataProvider provideEntities
     */
    public function testGetEntityPropertyValue(
        $expected,
        ApiEntityInterface $entity,
        Stringy $field,
        array $args = []
    ): void {
        $valueGetter = new ValueGetter();
        $value = $valueGetter->getEntityPropertyValue($entity, $field, $args);
        $this->assertEquals($expected, $value);
    }

    /**
     * @param ApiEntityInterface|null $expected
     * @param ApiEntityInterface      $entity
     * @param Stringy                 $field
     *
     * @throws \Safe\Exceptions\StringsException
     *
     * @dataProvider provideRelatedEntities
     */
    public function testGetRelatedEntityValue(
        ?ApiEntityInterface $expected,
        ApiEntityInterface $entity,
        Stringy $field
    ): void {
        $valueGetter = new ValueGetter();
        $value = $valueGetter->getRelatedEntityValue($entity, $field);
        $this->assertEquals($expected, $value);
    }

    /**
     * @param RA                      $expected
     * @param ApiEntityInterface      $entity
     * @param Stringy                 $field
     * @param DoctrineCrudApiMetadata $metadata
     *
     * @throws \WernerDweight\RA\Exception\RAException
     *
     * @dataProvider provideRelatedCollections
     */
    public function testGetRelatedCollectionValue(
        RA $expected,
        ApiEntityInterface $entity,
        Stringy $field,
        DoctrineCrudApiMetadata $metadata
    ): void {
        $valueGetter = new ValueGetter();
        $value = $valueGetter->getRelatedCollectionValue($entity, $field, $metadata);
        $this->assertEquals($expected, $value);
    }

    /**
     * @return array
     */
    public function provideEntities(): array
    {
        return [
            [
                'kitten123',
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function getKitten(string $suffix): string
                    {
                        return 'kitten' . $suffix;
                    }
                },
                new Stringy('kitten'),
                ['123'],
            ],
            [
                'kitten',
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function getKitten(): string
                    {
                        return 'kitten';
                    }
                },
                new Stringy('kitten'),
            ],
            [
                true,
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function isKitten(): bool
                    {
                        return true;
                    }
                },
                new Stringy('kitten'),
            ],
            [
                'kitten',
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function kitten(): string
                    {
                        return 'kitten';
                    }
                },
                new Stringy('kitten'),
            ],
            [
                'kitten',
                new class() implements ApiEntityInterface {
                    public $kitten = 'kitten';

                    public function getId(): int
                    {
                        return 1;
                    }
                },
                new Stringy('kitten'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function provideRelatedEntities(): array
    {
        $relatedEntity = new class() implements ApiEntityInterface {
            public function getId(): int
            {
                return 1;
            }
        };
        return [
            [
                $relatedEntity,
                new class($relatedEntity) implements ApiEntityInterface {
                    private $relatedEntity;

                    public function __construct(ApiEntityInterface $entity)
                    {
                        $this->relatedEntity = $entity;
                    }

                    public function getId(): int
                    {
                        return 1;
                    }

                    public function getRelatedEntity(): ApiEntityInterface
                    {
                        return $this->relatedEntity;
                    }
                },
                new Stringy('relatedEntity'),
            ],
            [
                null,
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function getRelatedEntity(): ?ApiEntityInterface
                    {
                        return null;
                    }
                },
                new Stringy('relatedEntity'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function provideRelatedCollections(): array
    {
        $relatedCollection = new ArrayCollection();
        $relatedCollection->add(new class() implements ApiEntityInterface {
            public function getId(): int
            {
                return 1;
            }
        });
        $relatedCollection->add(new class() implements ApiEntityInterface {
            public function getId(): int
            {
                return 2;
            }
        });
        return [
            [
                new RA($relatedCollection->toArray()),
                new class($relatedCollection) implements ApiEntityInterface {
                    private $relatedCollection;

                    public function __construct(Collection $collection)
                    {
                        $this->relatedCollection = $collection;
                    }

                    public function getId(): int
                    {
                        return 1;
                    }

                    public function getRelatedCollection(): Collection
                    {
                        return $this->relatedCollection;
                    }
                },
                new Stringy('relatedCollection'),
                DoctrineCrudApiMetadataFixtures::createEmptyMetadata(),
            ],
            [
                new RA(),
                new class() implements ApiEntityInterface {
                    public function getId(): int
                    {
                        return 1;
                    }

                    public function getRelatedCollection(): Collection
                    {
                        return new ArrayCollection();
                    }
                },
                new Stringy('relatedCollection'),
                DoctrineCrudApiMetadataFixtures::createEmptyMetadata(),
            ],
        ];
    }
}
