<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use shanept\AssemblySimulator\Address\SibAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

class SibAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements()
    {
        $sibByte = ['s' => 1,'i' => 0,'b' => 0];

        $sib = new SibAddress(0, $sibByte, 0, 1);

        $this->assertInstanceOf(AddressInterface::class, $sib);
    }

    public static function sibAddressResolvesCorrectly()
    {
        return [
            // positive numbers
            [8, 19457, 4634635, 9746, 5, 0, 4800042],
            [8, 525745, 634525, 242373, 5, 30, 5082893],
            [8, 19457, 4634635, 127, 2, 0, 4790420],
            [8, 525745, 634525, 30, 2, 30, 4840547],
            [8, 19457, 4634635, 0, 1, 0, 4790292],
            [8, 525745, 634525, 0, 1, 30, 4840516],
            [4, 34266, 6534335, 255, 5, 23, 6671682],
            [4, 194, 643635, 754425, 5, 99, 1398940],
            [4, 34266, 6534335, 100, 2, 23, 6671524],
            [4, 194, 643635, 55, 2, 99, 644567],
            [4, 34266, 6534335, 0, 1, 23, 6671423],
            [4, 194, 643635, 0, 1, 99, 644511],
            [2, 345, 54532, 9203454, 5, 101, 9258782],
            [2, 643, 65484, 9204853, 5, 7, 9271635],
            [2, 345, 54532, 13, 2, 101, 55338],
            [2, 643, 65484, 62, 2, 7, 66841],
            [2, 345, 54532, 0, 1, 101, 55324],
            [2, 643, 65484, 0, 1, 7, 66778],
            [1, 432, 234324, 92045437, 5, 2222, 92282420],
            [1, 6575, 85745, 9203254, 5, 15, 9295594],
            [1, 432, 234324, 49, 2, 2222, 237029],
            [1, 6575, 85745, 109, 2, 15, 92446],
            [1, 432, 234324, 0, 1, 2222, 236979],
            [1, 6575, 85745, 0, 1, 15, 92336],

            // negative numbers
            [8, 19457, 4634635, 0xFFFFD9EE, 5, 0, 4780550],
            [8, 525745, 634525, 0xFFFC4D3B, 5, 30, 4598147],
            [4, 34266, 6534335, 0xFFFFFF01, 5, 23, 6671172],
            [4, 194, 643635, 0xFFFF7D07, 5, 99, 610986],
            [2, 345, 54532, 0xFFFFA462, 5, 101, 31874],
            [2, 643, 65484, 0xFFFF0DA0, 5, 7, 4734],
            [1, 432, 234324, 0xFFFE6423, 5, 2222, 131546],
            [1, 6575, 85745, 0xFFFFB09B, 5, 15, 72015],

            [8, 19457, 4634635, 0xEF, 2, 0, 4790276],
            [8, 525745, 634525, 0xDC, 2, 30, 4840481],
            [4, 34266, 6534335, 0xA4, 2, 23, 6671332],
            [4, 194, 643635, 0x99, 2, 99, 644409],
            [2, 345, 54532, 0xCD, 2, 101, 55274],
            [2, 643, 65484, 0x83, 2, 7, 66654],
            [1, 432, 234324, 0x95, 2, 2222, 236873],
            [1, 6575, 85745, 0xBA, 2, 15, 92267],
        ];
    }

    /**
     * @dataProvider sibAddressResolvesCorrectly
     */
    public function testSibAddressResolvesCorrectly(
        $scale,
        $index,
        $base,
        $displacement,
        $dispSize,
        $instPointer,
        $expected,
    ) {
        $sib = [
            's' => $scale,
            'i' => $index,
            'b' => $base,
        ];

        $sib = new SibAddress($instPointer, $sib, $displacement, $dispSize);

        $this->assertEquals($expected, $sib->getAddress());
    }

    public function testDisplacementReturnsIntFromConstruct()
    {
        $sibByte = ['s' => 1,'i' => 0,'b' => 0];

        $sib0 = new SibAddress(0, $sibByte, 0, 1);
        $sib8 = new SibAddress(0, $sibByte, 0, 2);
        $sib32 = new SibAddress(0, $sibByte, 0, 5);

        $this->assertEquals(1, $sib0->getDisplacement());
        $this->assertEquals(2, $sib8->getDisplacement());
        $this->assertEquals(5, $sib32->getDisplacement());
    }

    public static function invalidDisplacementThrowsExceptionDataProvider()
    {
        return [[-5], [-4], [-3], [-2], [-1], [0], [3], [4], [6], [7], [8], [9], [10]];
    }

    /**
     * @dataProvider invalidDisplacementThrowsExceptionDataProvider
     */
    public function testInvalidDisplacementThrowsException($displacementSize)
    {
        $sibByte = ['s' => 1,'i' => 0,'b' => 0];

        $this->expectException(\UnexpectedValueException::class);
        $sib0 = new SibAddress(0, $sibByte, 0, $displacementSize);
    }

    public static function invalidScaleThrowsExceptionDataProvider()
    {
        return [[-5], [-4], [-3], [-2], [-1], [0], [3], [5], [6], [7], [9], [10]];
    }

    /**
     * @dataProvider invalidScaleThrowsExceptionDataProvider
     */
    public function testInvalidScaleThrowsException($scale)
    {
        $sibByte = ['s' => $scale,'i' => 0,'b' => 0];

        $this->expectException(\UnexpectedValueException::class);
        $sib0 = new SibAddress(0, $sibByte, 0, 1);
    }
}
