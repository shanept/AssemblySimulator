<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use PHPUnit\Framework\TestCase;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\LoadEffectiveAddress;

/**
 * @covers shanept\AssemblySimulator\Instruction\LoadEffectiveAddress
 */
class LoadEffectiveAddressTest extends TestCase
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
                  ->method('getCodeAtInstructionPointer')
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
     * @return array<int, array{int, int, array<int, int>, RegisterObj, int, RegisterObj, int, string, ?string, int}>
     */
    public static function leaLoadsAddressDataProvider(): array
    {
        return [
            // lea r10d,[r9+0x17]
            // REX.RB 0x45 0x8d 0x51 0x17
            [Simulator::LONG_MODE, 0x45, [0x67], Register::R9D, 1, Register::R10D, 0x18, "\x51", "\x17", 1],

            // lea edx,[ecx+0x5]
            // 0x8d 0x51 0x5
            [Simulator::LONG_MODE, 0x00, [0x67], Register::ECX, 13, Register::EDX, 0x12, "\x51", "\x5", 22],

            [Simulator::LONG_MODE, 0x00, [0x66, 0x67], Register::ECX, 65536, Register::DX, 0x12, "\x51", "\x12", 43],
        ];
    }

    /**
     * @dataProvider leaLoadsAddressDataProvider
     * @small
     *
     * @param array<int, int> $prefixValues
     * @param RegisterObj $readRegister
     * @param RegisterObj $writeRegister
     */
    public function testLeaLoadsAddress(
        int $mode,
        int $rexValue,
        array $prefixValues,
        array $readRegister,
        int $readValue,
        array $writeRegister,
        int $writeValue,
        string $modRmByte,
        ?string $displacement,
        int $instructionPointer
    ): void {
        $simulator = $this->getMockSimulator($mode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requestedPrefix) use ($prefixValues): bool {
                      return in_array($requestedPrefix, $prefixValues, true);
                  });

        $simulator->expects($this->once())
                  ->method('readRegister')
                  ->willReturn($readValue)
                  ->with($readRegister);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($writeRegister, $writeValue);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstructionPointer')
                  ->willReturn($modRmByte)
                  ->with(1);

        $dispLen = 0;
        if (! is_null($displacement)) {
            $dispLen = strlen($displacement);

            $simulator->expects($this->once())
                      ->method('getCodeBuffer')
                      ->willReturn($displacement)
                      ->with($instructionPointer + 2, $dispLen);
        }

        $simulator->method('getInstructionPointer')
                  ->willReturnCallback(function () use (&$instructionPointer): int {
                      return $instructionPointer;
                  });

        $simulator->expects($this->atLeastOnce())
                  ->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$instructionPointer): void {
                      $instructionPointer += $amount;
                  });

        $expectedInstructionPointer = $instructionPointer + 2 + $dispLen;

        $lea = new LoadEffectiveAddress();
        $lea->setSimulator($simulator);

        $this->assertTrue($lea->executeOperand8d());
        $this->assertEquals($expectedInstructionPointer, $instructionPointer);
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
                  ->method('getCodeAtInstructionPointer')
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
