<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use PHPUnit\Framework\TestCase;
use shanept\AssemblySimulator\Address\RipAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

/**
 * @covers shanept\AssemblySimulator\Address\RipAddress
 */
class RipAddressTest extends TestCase
{
    /**
     * @small
     */
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
            [100, 30, 0, 134],
            [32, 50, 0, 86],
            [100, 30, 15, 149],
            [32, 50, -15, 71],

            // negative numbers
            [0, 0x80000009, 0, -0x7FFFFFF3],
            [17, 0xFFFFFFF0, 0, 5],
            [95, 0xFFFFFFE2, -15, 54],
            [17, 0xFFFFFFF0, 15, 20],
        ];
    }

    /**
     * @dataProvider ripAddressResolvesCorrectly
     * @small
     */
    public function testRipAddressResolvesCorrectly(
        int $ripPointer,
        int $address,
        int $offset,
        int $expected
    ): void {
        $rip = new RipAddress($ripPointer, $address);

        $this->assertEquals($expected, $rip->getAddress($offset));
    }

    /**
     * @dataProvider ripAddressResolvesCorrectly
     * @small
     */
    public function testRipAddressWithoutOffsetResolvesCorrectly(
        int $ripPointer,
        int $address,
        int $offset,
        int $expected
    ): void {
        $rip = new RipAddress($ripPointer, $address);

        $this->assertEquals($expected - $offset, $rip->getAddress());
    }

    /**
     * @small
     */
    public function testRipDisplacementIs32bit(): void
    {
        $rip = new RipAddress(0, 0);

        $this->assertEquals(4, $rip->getDisplacement());
    }
}
