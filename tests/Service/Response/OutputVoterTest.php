<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Response;

use PHPUnit\Framework\TestCase;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\OutputVoter;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\Printer;
use WernerDweight\Stringy\Stringy;

class OutputVoterTest extends TestCase
{
    /**
     * @param bool $expected
     * @param Stringy $field
     * @param DoctrineCrudApiMetadata $metadata
     * @param RA|null $responseStructure
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
     * @return array
     */
    public function provideValues(): array
    {
        return [
            [true, 'this.title'],
            [true, 'this.author'],
            [true, 'this.author.name'],
            [false, 'this.author.email'],
            [false, 'this.category'],
        ];
    }
}
