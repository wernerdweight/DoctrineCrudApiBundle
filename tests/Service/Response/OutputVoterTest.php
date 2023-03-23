<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Response;

use PHPUnit\Framework\TestCase;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\OutputVoter;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\DoctrineCrudApiMetadataFixtures;
use WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures\DoctrineCrudApiResponseStructureFixtures;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class OutputVoterTest extends TestCase
{
    /**
     * @throws \WernerDweight\RA\Exception\RAException
     *
     * @dataProvider provideValues
     */
    public function testVote(
        bool $expected,
        Stringy $field,
        DoctrineCrudApiMetadata $metadata,
        ?RA $responseStructure
    ): void {
        $outputVoter = new OutputVoter();
        $value = $outputVoter->vote($field, $metadata, $responseStructure);
        $this->assertEquals($expected, $value);
    }

    /**
     * @return mixed[]
     */
    public static function provideValues(): array
    {
        $metadata = DoctrineCrudApiMetadataFixtures::createArticleMetadata();
        $responseStructure = DoctrineCrudApiResponseStructureFixtures::createArticleResponseStructure();
        return [
            [true, new Stringy('article.title'), $metadata, $responseStructure],
            [true, new Stringy('article.author'), $metadata, $responseStructure],
            [true, new Stringy('article.author.name'), $metadata, $responseStructure],
            [false, new Stringy('article.author.email'), $metadata, $responseStructure],
            [false, new Stringy('article.category'), $metadata, $responseStructure],
        ];
    }
}
