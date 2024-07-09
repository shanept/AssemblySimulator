<?php

namespace shanept\AssemblySimulatorTests\Integration;

use PHPUnit\Framework\TestCase;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\ExclusiveOr;

/**
 * @covers shanept\AssemblySimulator\Simulator
 */
class RexTest extends TestCase
{
    public function testNonRexOpAfterRexOp(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);
        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $simulator->writeRegister(Register::R8, PHP_INT_MAX);
        $simulator->writeRegister(Register::R9, 0x3555555555555555);
        $simulator->writeRegister(Register::EAX, 0xFFFFFFFF);
        $simulator->writeRegister(Register::ECX, 0x35555555);

        // REX.RB xor r8,r9
        // xor eax,ecx
        $simulator->setCodeBuffer("\x4D\x31\xC8\x31\xC8");
        $simulator->simulate();

        $r8val = $simulator->readRegister(Register::R8);
        $eaxVal = $simulator->readRegister(Register::EAX);

        $this->assertEquals(0x4AAAAAAAAAAAAAAA, $r8val);
        $this->assertEquals(0xCAAAAAAA, $eaxVal);
    }
}
