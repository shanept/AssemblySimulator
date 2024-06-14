<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use shanept\AssemblySimulator\Address\ModRmAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

class ModRmAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements()
    {
        $modrm = new ModRmAddress(0, 0, 1);

        $this->assertInstanceOf(AddressInterface::class, $modrm);
    }

    public static function modRmAddressResolvesCorrectly()
    {
        return [
            // positive numbers
            [4634635, 9746, 4, 0, 4644381],
            [634525, 242373, 4, 100, 876998],
            [4634635, 127, 1, 0, 4634762],
            [634525, 30, 1, 30, 634585],
            [6534335, 255, 4, 0, 6534590],
            [643635, 754425, 4, 0, 1398060],
            [6534335, 100, 1, 0, 6534435],
            [643635, 55, 1, 0, 643690],
            [54532, 9203454, 4, 0, 9257986],
            [65484, 9204853, 4, 0, 9270337],
            [54532, 13, 1, 0, 54545],
            [65484, 62, 1, 25, 65571],
            [234324, 92045437, 4, 0, 92279761],
            [85745, 9203254, 4, 0, 9288999],
            [234324, 49, 1, 36, 234409],
            [85745, 109, 1, 0, 85854],
            [234324, 0, 0, 0, 234324],
            [85745, 0, 0, 0, 85745],
            [85745, 0, 0, 1, 85746],

            // negative numbers
            [4634635, 0xFFFFD9EE, 4, 0, 4624889],
            [634525, 0xFFFC4D3B, 4, 0, 392152],
            [4634635, 0xEF, 1, 0, 4634618],
            [634525, 0xDC, 1, 0, 634489],
            [634525, 0xDC, 1, 0, 634489],
            [6534335, 0xFFFFFF01, 4, 0, 6534080],
            [643635, 0xFFFF7D07, 4, 0, 610106],
            [6534335, 0xA4, 1, 0, 6534243],
            [643635, 0x99, 1, 0, 643532],
            [643635, 0x99, 1, 0, 643532],
            [54532, 0xFFFFA462, 4, 0, 31078],
            [65484, 0xFFFF0DA0, 4, 0, 3436],
            [54532, 0xCD, 1, 0, 54481],
            [65484, 0x83, 1, 0, 65359],
            [65484, 0x83, 1, 0, 65359],
            [234324, 0xFFFE6423, 4, 0, 128887],
            [85745, 0xFFFFB09B, 4, 0, 65420],
            [234324, 0x95, 1, 0, 234217],
            [85745, 0xBA, 1, 0, 85675],
            [85745, 0xBA, 1, 0, 85675],
        ];
    }

    /**
     * @dataProvider modRmAddressResolvesCorrectly
     */
    public function testModRmAddressResolvesCorrectly(
        $baseAddress,
        $displacement,
        $dispSize,
        $offset,
        $expected,
    ) {
        $modrm = new ModRmAddress($baseAddress, $displacement, $dispSize);

        $this->assertEquals($expected, $modrm->getAddress($offset));
    }

    public function testDisplacementReturnsIntFromConstruct()
    {
        $modrm1 = new ModRmAddress(0, 0, 5);
        $modrm2 = new ModRmAddress(0, 0, 3);

        $this->assertEquals(5, $modrm1->getDisplacement());
        $this->assertEquals(3, $modrm2->getDisplacement());
    }
}
