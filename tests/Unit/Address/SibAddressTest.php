<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use shanept\AssemblySimulator\Address\SibAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

/**
 * @covers shanept\AssemblySimulator\Address\SibAddress
 */
class SibAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @small
     */
    public function testImplements(): void
    {
        $sibByte = ['s' => 1,'i' => 0,'b' => 0];

        $sib = new SibAddress($sibByte, 0, 1);

        $this->assertInstanceOf(AddressInterface::class, $sib);
    }

    /**
     * @return array<int, array{int, int, int, int, int, int}>
     */
    public static function sibAddressResolvesCorrectly(): array
    {
        return [
            // positive numbers
            [8, 19457, 4634635, 9746, 5, 0, 4800037],
            [8, 525745, 634525, 242373, 5, 0, 5082858],
            [8, 19457, 4634635, 127, 2, 0, 4790418],
            [8, 525745, 634525, 30, 2, 0, 4840515],
            [8, 19457, 4634635, 0, 1, 0, 4790291],
            [8, 525745, 634525, 0, 1, 0, 4840485],
            [8, 525745, 634525, 0, 1, 5, 4840490],
            [4, 34266, 6534335, 255, 5, 0, 6671654],
            [4, 194, 643635, 754425, 5, 0, 1398836],
            [4, 34266, 6534335, 100, 2, 0, 6671499],
            [4, 194, 643635, 55, 2, 0, 644466],
            [4, 34266, 6534335, 0, 1, 0, 6671399],
            [4, 194, 643635, 0, 1, 0, 644411],
            [4, 194, 643635, 0, 1, -5, 644406],
            [2, 345, 54532, 9203454, 5, 0, 9258676],
            [2, 643, 65484, 9204853, 5, 0, 9271623],
            [2, 345, 54532, 13, 2, 0, 55235],
            [2, 643, 65484, 62, 2, 0, 66832],
            [2, 345, 54532, 0, 1, 0, 55222],
            [2, 643, 65484, 0, 1, 0, 66770],
            [2, 643, 65484, 0, 1, 12, 66782],
            [1, 432, 234324, 92045437, 5, 0, 92280193],
            [1, 6575, 85745, 9203254, 5, 0, 9295574],
            [1, 432, 234324, 49, 2, 0, 234805],
            [1, 6575, 85745, 109, 2, 0, 92429],
            [1, 432, 234324, 0, 1, 0, 234756],
            [1, 6575, 85745, 0, 1, 0, 92320],
            [1, 6575, 85745, 0, 1, -30, 92290],

            // negative numbers
            [8, 19457, 4634635, 0xFFFFD9EE, 5, 0, 4780545],
            [8, 525745, 634525, 0xFFFC4D3B, 5, 0, 4598112],
            [8, 19457, 4634635, 0xEF, 2, 0, 4790274],
            [8, 525745, 634525, 0xDC, 2, 0, 4840449],
            [8, 525745, 634525, 0xDC, 2, -5, 4840444],
            [4, 34266, 6534335, 0xFFFFFF01, 5, 0, 6671144],
            [4, 194, 643635, 0xFFFF7D07, 5, 0, 610882],
            [4, 34266, 6534335, 0xA4, 2, 0, 6671307],
            [4, 194, 643635, 0x99, 2, 0, 644308],
            [4, 194, 643635, 0x99, 2, 10, 644318],
            [2, 345, 54532, 0xFFFFA462, 5, 0, 31768],
            [2, 643, 65484, 0xFFFF0DA0, 5, 0, 4722],
            [2, 345, 54532, 0xCD, 2, 0, 55171],
            [2, 643, 65484, 0x83, 2, 0, 66645],
            [2, 643, 65484, 0x83, 2, -3, 66642],
            [1, 432, 234324, 0xFFFE6423, 5, 0, 129319],
            [1, 6575, 85745, 0xFFFFB09B, 5, 0, 71995],
            [1, 432, 234324, 0x95, 2, 0, 234649],
            [1, 6575, 85745, 0xBA, 2, 0, 92250],
            [1, 6575, 85745, 0xBA, 2, 15, 92265],
        ];
    }

    /**
     * @dataProvider sibAddressResolvesCorrectly
     * @small
     */
    public function testSibAddressResolvesCorrectly(
        int $scale,
        int $index,
        int $base,
        int $displacement,
        int $dispSize,
        int $offset,
        int $expected
    ): void {
        $sib = [
            's' => $scale,
            'i' => $index,
            'b' => $base,
        ];

        $sib = new SibAddress($sib, $displacement, $dispSize);

        $this->assertEquals($expected, $sib->getAddress($offset));
    }

    /**
     * @dataProvider sibAddressResolvesCorrectly
     * @small
     */
    public function testSibAddressWithoutOffsetResolvesCorrectly(
        int $scale,
        int $index,
        int $base,
        int $displacement,
        int $dispSize,
        int $offset,
        int $expected
    ): void {
        $sib = [
            's' => $scale,
            'i' => $index,
            'b' => $base,
        ];

        $sib = new SibAddress($sib, $displacement, $dispSize);

        $this->assertEquals($expected - $offset, $sib->getAddress());
    }

    /**
     * @small
     */
    public function testDisplacementReturnsIntFromConstruct(): void
    {
        $sibByte = ['s' => 1,'i' => 0,'b' => 0];

        $sib0 = new SibAddress($sibByte, 0, 1);
        $sib8 = new SibAddress($sibByte, 0, 2);
        $sib32 = new SibAddress($sibByte, 0, 5);

        $this->assertEquals(1, $sib0->getDisplacement());
        $this->assertEquals(2, $sib8->getDisplacement());
        $this->assertEquals(5, $sib32->getDisplacement());
    }

    /**
     * @return array<int, array{int}>
     */
    public static function invalidDisplacementThrowsExceptionDataProvider(): array
    {
        return [[-5], [-4], [-3], [-2], [-1], [0], [3], [4], [6], [7], [8], [9], [10]];
    }

    /**
     * @dataProvider invalidDisplacementThrowsExceptionDataProvider
     * @small
     */
    public function testInvalidDisplacementThrowsException(int $displacementSize): void
    {
        $sibByte = ['s' => 1,'i' => 0,'b' => 0];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid SIB address length %d. Expected 1 (no displacement), ' .
            '2 (8-bit displacement) or 5 (32-bit displacement).',
            $displacementSize,
        ));
        $sib0 = new SibAddress($sibByte, 0, $displacementSize);
    }

    /**
     * @return array<int, array{int}>
     */
    public static function invalidScaleThrowsExceptionDataProvider(): array
    {
        return [[-5], [-4], [-3], [-2], [-1], [0], [3], [5], [6], [7], [9], [10]];
    }

    /**
     * @dataProvider invalidScaleThrowsExceptionDataProvider
     * @small
     */
    public function testInvalidScaleThrowsException(int $scale): void
    {
        $sibByte = ['s' => $scale,'i' => 0,'b' => 0];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid SIB scale value %d. Expected 1, 2, 4 or 8.',
            $scale,
        ));
        $sib0 = new SibAddress($sibByte, 0, 1);
    }
}
