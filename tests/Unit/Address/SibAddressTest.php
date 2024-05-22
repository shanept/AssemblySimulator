<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use shanept\AssemblySimulator\Address\SibAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

class SibAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements()
    {
        $sib = new SibAddress(0, [], 0, 0);

        $this->assertInstanceOf(AddressInterface::class, $sib);
    }

    public static function sibAddressResolvesCorrectly()
    {
        return [
            // positive numbers
            [8, 19457, 4634635, 9746, 0, 4800042],
            [8, 525745, 634525, 242373, 30, 5082893],
            [4, 34266, 6534335, 255, 23, 6671682],
            [4, 194, 643635, 754425, 99, 1398940],
            [2, 345, 54532, 9203454, 101, 9258782],
            [2, 643, 65484, 9204853, 7, 9271635],
            [1, 432, 234324, 92045437, 2222, 92282420],
            [1, 6575, 85745, 9203254, 15, 9295594],

            // negative numbers
            [8, 19457, 4634635, 0xFFFFD9EE, 0, 4780550],
            [8, 525745, 634525, 0xFFFC4D3B, 30, 4598147],
            [4, 34266, 6534335, 0xFFFFFF01, 23, 6671172],
            [4, 194, 643635, 0xFFFF7D07, 99, 610986],
            [2, 345, 54532, 0xFFFFA462, 101, 31874],
            [2, 643, 65484, 0xFFFF0DA0, 7, 4734],
            [1, 432, 234324, 0xFFFE6423, 2222, 131546],
            [1, 6575, 85745, 0xFFFFB09B, 15, 72015],
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
        $instPointer,
        $expected,
    ) {
        $sib = [
            's' => $scale,
            'i' => $index,
            'b' => $base,
        ];

        $sib = new SibAddress($instPointer, $sib, $displacement, 5);

        $this->assertEquals($expected, $sib->getAddress());
    }

    public function testDisplacementReturnsIntFromConstruct()
    {
        $sib0 = new SibAddress(0, [], 0, 1);
        $sib8 = new SibAddress(0, [], 0, 2);
        $sib32 = new SibAddress(0, [], 0, 5);

        $this->assertEquals(1, $sib0->getDisplacement());
        $this->assertEquals(2, $sib8->getDisplacement());
        $this->assertEquals(5, $sib32->getDisplacement());
    }
}
