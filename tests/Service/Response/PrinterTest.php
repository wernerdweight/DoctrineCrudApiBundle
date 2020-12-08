<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Service\Response;

use PHPUnit\Framework\TestCase;
use Safe\DateTime;
use WernerDweight\DoctrineCrudApiBundle\Service\Response\Printer;
use WernerDweight\Stringy\Stringy;

class PrinterTest extends TestCase
{
    /**
     * @param mixed $value
     *
     * @dataProvider provideValues
     *
     * @throws \Safe\Exceptions\PcreException
     */
    public function testPrint(string $expected, $value): void
    {
        $printer = new Printer();
        $value = $printer->print($value);
        $this->assertEquals($expected, $value);
    }

    /**
     * @return mixed[]
     *
     * @throws \Exception
     */
    public function provideValues(): array
    {
        return [
            ['null', null],
            ['true', true],
            ['false', false],
            ['123', 123],
            ['1.23', 1.23],
            ['array', [1, 2, 3]],
            ['some string', 'some string'],
            ['2019-07-03T09:40:00+0100', new DateTime('2019-07-03 09:40', new \DateTimeZone('Europe/London'))],
            ['string value', new Stringy('string value')],
            ['object', new \stdClass()],
        ];
    }
}
