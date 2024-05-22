<?php

namespace shanept\AssemblySimulatorTests\Unit\Instructions;

use shanept\AssemblySimulator\Flags;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\LoadEffectiveAddress;

class LeaTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    public function testLeaLoads64BitAddress()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);
        $simulator = $this->mockSimulatorRegisters($simulator);

        // lea rda,0xf1917e7
        // REX.W 0x48 0x8D 0x3D 0xE0 0x17 0x19 0x0F
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function($length) {
                      $values = [
                          1 => "\x3D",
                          4 => "\xE0\x17\x19\x0F"
                      ];
                      return $values[$length];
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturn(3);

        $lea = new LoadEffectiveAddress;
        $lea->setSimulator($simulator);

        $lea->executeOperand8d();

        $this->assertEquals(0xf1917e7, $simulator->readRegister(Register::RDI));
    }

    public function testLeaThrowsExceptionOnInvalidModBit()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);
        $simulator = $this->mockSimulatorRegisters($simulator);

        // REX.W lea rdi,0xf1917e7 (mod 2 - invalid)
        // 0x48 0x8D 0xFD 0xE0 0x17 0x19 0x0F (mod 2 - invalid)
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstruction')
                  ->willReturn("\xFD");

        $lea = new LoadEffectiveAddress;
        $lea->setSimulator($simulator);

        $this->expectException(\RuntimeException::class);
        $lea->executeOperand8d();
    }
}
