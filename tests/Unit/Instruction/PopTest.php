<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\Pop;

/**
 * @covers shanept\AssemblySimulator\Instruction\Pop
 */
class PopTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    /**
     * @return array<int, array{int, string, int, int, RegisterObj, RegisterObj, int, string}>
     */
    public static function popOffStackDataProvider(): array
    {
        return [
            [Simulator::LONG_MODE, 64, "\x58", 0x49, 0x00, Register::RSP, Register::R8, 23452, "\x9C\x5B\x00\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x59", 0x49, 0x00, Register::RSP, Register::R9, 25234, "\x92\x62\x00\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5A", 0x49, 0x00, Register::RSP, Register::R10, 98034, "\xF2\x7E\x01\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5B", 0x49, 0x00, Register::RSP, Register::R11, 897696, "\xA0\xB2\x0D\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5C", 0x49, 0x00, Register::RSP, Register::R12, 87886, "\x4E\x57\x01\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5D", 0x49, 0x00, Register::RSP, Register::R13, 234245, "\x05\x93\x03\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5E", 0x49, 0x00, Register::RSP, Register::R14, 5254356, "\xD4\x2C\x50\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5F", 0x49, 0x00, Register::RSP, Register::R15, 524534, "\xF6\x00\x08\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x58", 0x48, 0x00, Register::RSP, Register::RAX, 64353, "\x61\xFB\x00\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x59", 0x48, 0x00, Register::RSP, Register::RCX, 1221455, "\x4F\xA3\x12\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5A", 0x48, 0x00, Register::RSP, Register::RDX, 232526, "\x4E\x8C\x03\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5B", 0x48, 0x00, Register::RSP, Register::RBX, 75345, "\x51\x26\x01\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5E", 0x48, 0x00, Register::RSP, Register::RSI, 234252, "\x0C\x93\x03\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 64, "\x5F", 0x48, 0x00, Register::RSP, Register::RDI, 754634, "\xCA\x83\x0B\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, 32, "\x58", 0x00, 0x00, Register::RSP, Register::EAX, 21414, "\xA6\x53\x00\x00"],
            [Simulator::LONG_MODE, 32, "\x59", 0x00, 0x00, Register::RSP, Register::ECX, 55235, "\xC3\xD7\x00\x00"],
            [Simulator::LONG_MODE, 32, "\x5A", 0x00, 0x00, Register::RSP, Register::EDX, 55234, "\xC2\xD7\x00\x00"],
            [Simulator::LONG_MODE, 32, "\x5B", 0x00, 0x00, Register::RSP, Register::EBX, 75464, "\xC8\x26\x01\x00"],
            [Simulator::LONG_MODE, 32, "\x5E", 0x00, 0x00, Register::RSP, Register::ESI, 3462365, "\xDD\xD4\x34\x00"],
            [Simulator::LONG_MODE, 32, "\x5F", 0x00, 0x00, Register::RSP, Register::EDI, 745677, "\xCD\x60\x0B\x00"],
            [Simulator::LONG_MODE, 16, "\x58", 0x00, 0x66, Register::RSP, Register::AX, 44889, "\x59\xAF"],
            [Simulator::LONG_MODE, 16, "\x59", 0x00, 0x66, Register::RSP, Register::CX, 2342, "\x26\x09"],
            [Simulator::LONG_MODE, 16, "\x5A", 0x00, 0x66, Register::RSP, Register::DX, 28054, "\x96\x6D"],
            [Simulator::LONG_MODE, 16, "\x5B", 0x00, 0x66, Register::RSP, Register::BX, 41412, "\xC4\xA1"],
            [Simulator::LONG_MODE, 16, "\x5E", 0x00, 0x66, Register::RSP, Register::SI, 8176, "\xF0\x1F"],
            [Simulator::LONG_MODE, 16, "\x5F", 0x00, 0x66, Register::RSP, Register::DI, 63421, "\xBD\xF7"],
        ];
    }

    /**
     * @dataProvider popOffStackDataProvider
     * @small
     *
     * @param RegisterObj $stackPointerRegister
     * @param RegisterObj $destinationRegister
     */
    public function testPopOffStack(
        int $simulatorMode,
        int $opSize,
        string $opcode,
        int $rexValue,
        int $prefixValue,
        array $stackPointerRegister,
        array $destinationRegister,
        int $expectedInt,
        string $expectedBin
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue): bool {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->once())
                  ->method('readStackAt')
                  ->willReturn($expectedBin);

        $simulator->expects($this->once())
                  ->method('clearStackAt')
                  ->with(2, $opSize);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstructionPointer')
                  ->willReturn($opcode)
                  ->with(1);

        $simulator->expects($this->once())
                  ->method('readRegister')
                  ->willReturn(2)
                  ->with($stackPointerRegister);

        $simulator->expects($this->once())
                  ->method('advanceInstructionPointer')
                  ->with(1);

        // This is where our value is saved, thus asserted for.
        $simulator->expects($this->exactly(2))
                  ->method('writeRegister')
                  ->willReturnCallback(function ($register, $value) use (
                      $opSize,
                      $expectedInt,
                      $stackPointerRegister,
                      $destinationRegister
                  ) {
                      if (4 === $register['offset']) {
                          // Ensure we are using the correct stack pointer
                          $this->assertEquals($stackPointerRegister, $register);
                          $this->assertEquals(2 + $opSize, $value);
                      } elseif ($register === $destinationRegister) {
                          // Ensure we have popped the correct value into the register
                          $this->assertEquals($expectedInt, $value);
                      } else {
                          $this->fail('Unexpected register ' . $register['name']);
                      }
                  });

        $instruction = new Pop();
        $instruction->setSimulator($simulator);

        $this->assertTrue($instruction->executeOperand5x());
    }
}
