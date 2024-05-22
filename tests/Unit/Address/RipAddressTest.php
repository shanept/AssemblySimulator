<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use shanept\AssemblySimulator\Address\RipAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

class RipAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements()
    {
        $rip = new RipAddress(0, 0, 0);

        $this->assertInstanceOf(AddressInterface::class, $rip);
    }

    public static function ripAddressResolvesCorrectly()
    {
        return [
            // positive numbers
            [100, 30, 8, 142],
            [32, 50, 2, 88],

            // negative numbers
            [95, 0xFFFFFFE2, 7, 76],
            [17, 0xFFFFFFF0, 3, 8],
        ];
    }

    /**
     * @dataProvider ripAddressResolvesCorrectly
     */
    public function testRipAddressResolvesCorrectly(
        $ripPointer,
        $address,
        $offset,
        $expected,
    ) {
        $rip = new RipAddress($ripPointer, $address, $offset);

        $this->assertEquals($expected, $rip->getAddress());
    }

    public function testRipDisplacementIs32bit()
    {
        $rip = new RipAddress(0, 0, 0);

        $this->assertEquals(4, $rip->getDisplacement());
    }
}
