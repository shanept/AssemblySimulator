<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use shanept\AssemblySimulator\Address\RipAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

class RipAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements()
    {
        $rip = new RipAddress(0, 0);

        $this->assertInstanceOf(AddressInterface::class, $rip);
    }

    public static function ripAddressResolvesCorrectly()
    {
        return [
            // positive numbers
            [100, 30, 134],
            [32, 50, 86],

            // negative numbers
            [95, 0xFFFFFFE2, 69],
            [17, 0xFFFFFFF0, 5],
        ];
    }

    /**
     * @dataProvider ripAddressResolvesCorrectly
     */
    public function testRipAddressResolvesCorrectly(
        $ripPointer,
        $address,
        $expected,
    ) {
        $rip = new RipAddress($ripPointer, $address);

        $this->assertEquals($expected, $rip->getAddress());
    }

    public function testRipDisplacementIs32bit()
    {
        $rip = new RipAddress(0, 0);

        $this->assertEquals(4, $rip->getDisplacement());
    }
}
