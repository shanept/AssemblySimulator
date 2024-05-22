<?php

namespace shanept\AssemblySimulatorTests\Unit\Address;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Address\SibAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

class SibAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements()
    {
        $sib = new SibAddress(0, 0, [], '\x0', 0, 0);

        $this->assertInstanceOf(AddressInterface::class, $sib);
    }

    public function testSibAddressResolvesCorrectly()
    {
        // mov [eax+ebx*4],ecx
        // 89 0c 98
        $registers = [
            Register::EAX["offset"] => 1948657,
            Register::ECX["offset"] => 0,
            Register::EDX["offset"] => 0,
            Register::EBX["offset"] => 635,
            Register::ESP["offset"] => 0,
            Register::EBP["offset"] => 0,
            Register::ESI["offset"] => 0,
            Register::EDI["offset"] => 0,
        ];

        /**
         * No rex. No prefix.
         * Values pre-defined in EAX and EBX.
         * SIB byte is \x98. (Scale: 4, Index: EBX, Base: EAX)
         * Displacement is 4 bytes (32-bits)
         */
        $sib = new SibAddress(0, 0, $registers, "\x98", 4, 0);

        // Expect (scale * index) + base + displacement
        $expected =
            4 * $registers[Register::EBX["offset"]] +
            $registers[Register::EAX["offset"]] +
            4;

        $this->assertEquals($expected, $sib->getAddress());
    }

    public function testSibAddressResolvesRexCorrectly()
    {
        // mov [rax+rbx*4],ecx
        // 43 89 0c 98
        $registers = [
            Register::RAX["offset"] => 1948657,
            Register::RCX["offset"] => 0,
            Register::RDX["offset"] => 0,
            Register::RBX["offset"] => 635,
            Register::RSP["offset"] => 0,
            Register::RBP["offset"] => 0,
            Register::RSI["offset"] => 0,
            Register::RDI["offset"] => 0,
            Register::R8["offset"] => 429841241,
            Register::R9["offset"] => 0,
            Register::R10["offset"] => 0,
            Register::R11["offset"] => 539,
            Register::R12["offset"] => 0,
            Register::R13["offset"] => 0,
            Register::R14["offset"] => 0,
            Register::R15["offset"] => 0,
        ];

        $rex = Simulator::REX_B | Simulator::REX_X;

        /**
         * REX_BX. No prefix.
         * Values pre-defined in EAX and EBX.
         * SIB byte is \x98. (Scale: 4, Index: EBX, Base: EAX)
         * Displacement is 4 bytes (32-bits)
         */
        $sib = new SibAddress($rex, 0, $registers, "\x98", 4, 0);

        // Expect (scale * index) + base + displacement
        $expected =
            4 * $registers[Register::R11["offset"]] +
            $registers[Register::R8["offset"]] +
            4;

        $this->assertEquals($expected, $sib->getAddress());
    }

    public function testSibAddressResolvesCorrectlyWithOffset()
    {
        // mov [eax+ebx*4],ecx
        // 89 0c 98
        $registers = [
            Register::EAX["offset"] => 1948657,
            Register::ECX["offset"] => 0,
            Register::EDX["offset"] => 0,
            Register::EBX["offset"] => 635,
            Register::ESP["offset"] => 0,
            Register::EBP["offset"] => 0,
            Register::ESI["offset"] => 0,
            Register::EDI["offset"] => 0,
        ];

        /**
         * No rex. No prefix.
         * Values pre-defined in EAX and EBX.
         * SIB byte is \x98. (Scale: 4, Index: EBX, Base: EAX)
         * Displacement is 4 bytes (32-bits)
         */
        $sib = new SibAddress(0, 0, $registers, "\x98", 4, 0);

        // Expect (scale * index) + base + displacement
        $expected =
            4 * $registers[Register::EBX["offset"]] +
            $registers[Register::EAX["offset"]] +
            4;

        $this->assertEquals($expected + 40, $sib->getAddress(40));
    }

    public function testDisplacementReturnsIntFromConstruct()
    {
        $sib0 = new SibAddress(0, 0, [], "\x0", 0, 0);
        $sib8 = new SibAddress(0, 0, [], "\x0", 1, 0);
        $sib32 = new SibAddress(0, 0, [], "\x0", 4, 0);

        $this->assertEquals(0, $sib0->getDisplacement());
        $this->assertEquals(1, $sib8->getDisplacement());
        $this->assertEquals(4, $sib32->getDisplacement());
    }
}
