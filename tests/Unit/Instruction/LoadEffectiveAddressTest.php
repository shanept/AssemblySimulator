<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\LoadEffectiveAddress;

/**
 * @covers shanept\AssemblySimulator\Instruction\LoadEffectiveAddress
 */
class LoadEffectiveAddressTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    /**
     * @small
     */
    public function testLeaLoads64BitAddress(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // lea rda,0xf1917e7
        // REX.W 0x48 0x8D 0x3D 0xE0 0x17 0x19 0x0F
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::RDI, 0xF1917E7);

        $simulator->expects($this->atLeastOnce())
                  ->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) {
                      $values = [
                          1 => "\x3D",
                          4 => "\xE0\x17\x19\x0F",
                      ];
                      return $values[$length];
                  });

        $iPointer = 3;
        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturn($iPointer);

        $lea = new LoadEffectiveAddress();
        $lea->setSimulator($simulator);

        $this->assertTrue($lea->executeOperand8d());
        $this->assertEquals(9, $iPointer);
    }

    /**
     * @small
     */
    public function testLeaLoadsAddressWithDisplacement(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // lea edx,[r9+0x17]
        // REX.B 0x41 0x8d 0x51 0x17
        $simulator->method('getRex')
                  ->willReturn(0x41);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->expects($this->once())
                  ->method('readRegister')
                  ->willReturn(1)
                  ->with(Register::R9D);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::EDX, 0x18);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstruction')
                  ->willReturn("\x51")
                  ->with(1);

        $simulator->expects($this->once())
                  ->method('getCodeBuffer')
                  ->willReturn("\x17")
                  ->with(3, 1);

        $simulator->method('getInstructionPointer')
                  ->willReturn(3);

        $lea = new LoadEffectiveAddress();
        $lea->setSimulator($simulator);

        $this->assertTrue($lea->executeOperand8d());
    }

    /**
     * @small
     */
    public function testLeaThrowsExceptionOnInvalidModBit(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // REX.W lea rdi,0xf1917e7 (mod 2 - invalid)
        // 0x48 0x8D 0xFD 0xE0 0x17 0x19 0x0F (mod 2 - invalid)
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstruction')
                  ->willReturn("\xFD")
                  ->with(1);

        $lea = new LoadEffectiveAddress();
        $lea->setSimulator($simulator);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'LEA expected modrm mod byte to be a memory operand, register operand 0x3 received instead.',
        );
        $lea->executeOperand8d();
    }
}
