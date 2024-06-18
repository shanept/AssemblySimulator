<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use shanept\AssemblySimulator\Address\RipAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

class RipAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements(): void
    {
        $rip = new RipAddress(0, 0);

        $this->assertInstanceOf(AddressInterface::class, $rip);
    }

    /**
     * @return array<int, array{int, int, int}>
     */
    public static function ripAddressResolvesCorrectly(): array
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
        int $ripPointer,
        int $address,
        int $expected,
    ): void {
        $rip = new RipAddress($ripPointer, $address);

        $this->assertEquals($expected, $rip->getAddress());
    }

    public function testRipDisplacementIs32bit(): void
    {
        $rip = new RipAddress(0, 0);

        $this->assertEquals(4, $rip->getDisplacement());
    }
}
