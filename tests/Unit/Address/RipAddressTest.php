<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use shanept\AssemblySimulator\Address\RipAddress;
use shanept\AssemblySimulator\Address\AddressInterface;


class RipAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements()
    {
        $rip = new RipAddress(0,0,0);

        $this->assertInstanceOf(AddressInterface::class, $rip);
    }

    public function testRipAddressResolvesCorrectly()
    {
        $rip = new RipAddress(100,30,8);

        $this->assertEquals(142, $rip->getAddress());
    }


    public function testRipAddressResolvesCorrectlyWithOffset()
    {
        $rip = new RipAddress(200,50,6);

        $this->assertEquals(300, $rip->getAddress(40));
    }

    public function testRipDisplacementIs32bit()
    {
        $rip = new RipAddress(0, 0, 0);

        $this->assertEquals(4, $rip->getDisplacement());
    }
}
