<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use PHPUnit\Framework\TestCase;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\Push;

/**
 * @covers shanept\AssemblySimulator\Instruction\Push
 */
class PushTest extends TestCase
{
    use MockSimulatorTrait;

    /**
     * @return array<int, array{int, string, int, int, RegisterObj, int, RegisterObj, int, string}>
     */
    public static function pushOnStack5xDataProvider(): array
    {
        return [
            [Simulator::LONG_MODE, "\x50", 0x49, 0x00, 3, Register::RSP, 32, Register::R8, 43256146, "\x52\x09\x94\x02\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x51", 0x49, 0x00, 2, Register::RSP, 42, Register::R9, 625226, "\x4A\x8A\x09\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x52", 0x49, 0x00, 1, Register::RSP, 52, Register::R10, 4184, "\x58\x10\x00\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x53", 0x49, 0x00, 8, Register::RSP, 62, Register::R11, 98367322, "\x5A\xF7\xDC\x05\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x54", 0x49, 0x00, 1, Register::RSP, 72, Register::R12, 414885, "\xA5\x54\x06\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x55", 0x49, 0x00, 8, Register::RSP, 82, Register::R13, 98765, "\xCD\x81\x01\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x56", 0x49, 0x00, 5, Register::RSP, 92, Register::R14, 2522342, "\xE6\x7C\x26\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x57", 0x49, 0x00, 4, Register::RSP, 10, Register::R15, 7456433, "\xB1\xC6\x71\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x50", 0x48, 0x00, 1, Register::RSP, 11, Register::RAX, 4152463, "\x8F\x5C\x3F\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x51", 0x48, 0x00, 7, Register::RSP, 12, Register::RCX, 8787464, "\x08\x16\x86\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x52", 0x48, 0x00, 2, Register::RSP, 13, Register::RDX, 42114356, "\x34\x9D\x82\x02\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x53", 0x48, 0x00, 4, Register::RSP, 14, Register::RBX, 74574567, "\xE7\xEA\x71\x04\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x55", 0x48, 0x00, 2, Register::RSP, 15, Register::RBP, 423425, "\x01\x76\x06\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x56", 0x48, 0x00, 6, Register::RSP, 16, Register::RSI, 764343, "\xB7\xA9\x0B\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x57", 0x48, 0x00, 2, Register::RSP, 17, Register::RDI, 5252636, "\x1C\x26\x50\x00\x00\x00\x00\x00"],
            [Simulator::LONG_MODE, "\x50", 0x00, 0x00, 1, Register::RSP, 18, Register::EAX, 412737, "\x41\x4C\x06\x00"],
            [Simulator::LONG_MODE, "\x51", 0x00, 0x00, 4, Register::RSP, 19, Register::ECX, 7457563, "\x1B\xCB\x71\x00"],
            [Simulator::LONG_MODE, "\x52", 0x00, 0x00, 2, Register::RSP, 20, Register::EDX, 52844, "\x6C\xCE\x00\x00"],
            [Simulator::LONG_MODE, "\x53", 0x00, 0x00, 2, Register::RSP, 21, Register::EBX, 1234252, "\x4C\xD5\x12\x00"],
            [Simulator::LONG_MODE, "\x55", 0x00, 0x00, 6, Register::RSP, 22, Register::EBP, 764336, "\xB0\xA9\x0B\x00"],
            [Simulator::LONG_MODE, "\x56", 0x00, 0x00, 2, Register::RSP, 23, Register::ESI, 525234, "\xB2\x03\x08\x00"],
            [Simulator::LONG_MODE, "\x57", 0x00, 0x00, 5, Register::RSP, 24, Register::EDI, 75643, "\x7B\x27\x01\x00"],
            [Simulator::LONG_MODE, "\x50", 0x00, 0x66, 0, Register::RSP, 25, Register::AX, 10373, "\x85\x28"],
            [Simulator::LONG_MODE, "\x51", 0x00, 0x66, 2, Register::RSP, 26, Register::CX, 52323, "\x63\xCC"],
            [Simulator::LONG_MODE, "\x52", 0x00, 0x66, 0, Register::RSP, 27, Register::DX, 10346, "\x6A\x28"],
            [Simulator::LONG_MODE, "\x53", 0x00, 0x66, 6, Register::RSP, 28, Register::BX, 56653, "\x4D\xDD"],
            [Simulator::LONG_MODE, "\x55", 0x00, 0x66, 0, Register::RSP, 29, Register::BP, 1056, "\x20\x04"],
            [Simulator::LONG_MODE, "\x56", 0x00, 0x66, 2, Register::RSP, 30, Register::SI, 62382, "\xAE\xF3"],
            [Simulator::LONG_MODE, "\x57", 0x00, 0x66, 3, Register::RSP, 31, Register::DI, 43262, "\xFE\xA8"],
        ];
    }

    /**
     * @dataProvider pushOnStack5xDataProvider
     * @small
     *
     * @param RegisterObj $stackPointer
     * @param RegisterObj $expectedRegister
     */
    public function testPushOnStack5x(
        int $simulatorMode,
        string $opcode,
        int $rexValue,
        int $prefixValue,
        int $instructionPointer,
        array $stackPointer,
        int $stackPosition,
        array $expectedRegister,
        int $regValue,
        string $stackValue
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);
        $this->mockInstructionPointer($simulator, $instructionPointer);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturn($opcode)
                  ->with(1);

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register) use (
                      $stackPointer,
                      $stackPosition,
                      $expectedRegister,
                      $regValue
                  ) {
                      if (4 === $register['offset']) {
                          $this->assertEquals($stackPointer, $register);
                          return $stackPosition;
                      } else {
                          $this->assertEquals($expectedRegister, $register);
                          return $regValue;
                      }
                  });

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($stackPointer, $stackPosition - strlen($stackValue));

        $simulator->expects($this->once())
                  ->method('writeStackAt')
                  ->with($stackPosition - strlen($stackValue), $stackValue);

        $expectedPointer = $instructionPointer + 1;

        $instruction = new Push();
        $instruction->setSimulator($simulator);

        $this->assertTrue($instruction->executeOperand5x());
        $this->assertEquals($expectedPointer, $instructionPointer);
    }

    /**
     * @return array<int, array{int, int, int, string, int, RegisterObj, int, string}>
     */
    public static function pushImmediateOnStackDataProvider(): array
    {
        return [
            [Simulator::LONG_MODE, 0x48, 0, "\x68", 2, Register::RSP, 33, "\x34\x00\x42\x00"],
            [Simulator::LONG_MODE, 0, 0, "\x68", 2, Register::RSP, 16, "\x84\x12\x58\x12"],
            [Simulator::LONG_MODE, 0, 0x66, "\x68", 2, Register::RSP, 7, "\x36\x45"],

            [Simulator::REAL_MODE, 0, 0, "\x6A", 3, Register::SP, 12, "\x59"],
            [Simulator::PROTECTED_MODE, 0, 0, "\x6A", 3, Register::ESP, 92, "\x42"],
            [Simulator::LONG_MODE, 0, 0, "\x6A", 3, Register::RSP, 3, "\x43"],
        ];
    }

    /**
     * @dataProvider pushImmediateOnStackDataProvider
     * @small
     *
     * @param RegisterObj $stackPointer
     */
    public function testPushImmediateOnStack(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        int $instructionPointer,
        array $stackPointer,
        int $stackPosition,
        string $immediate
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);
        $this->mockInstructionPointer($simulator, $instructionPointer);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturn($immediate)
                  ->with(strlen($immediate));

        $simulator->method('readRegister')
                  ->willReturn($stackPosition)
                  ->with($stackPointer);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($stackPointer, $stackPosition - strlen($immediate));

        $simulator->expects($this->once())
                  ->method('writeStackAt')
                  ->with($stackPosition - strlen($immediate), $immediate);

        $expectedPointer = $instructionPointer + 1 + strlen($immediate);

        $instruction = new Push();
        $instruction->setSimulator($simulator);

        $functionName = sprintf('executeOperand%02X', ord($opcode));
        $callable = [$instruction, $functionName];

        if (! is_callable($callable)) {
            $this->fail("Method Push::{$functionName} does not exist.");
        }

        $this->assertTrue(call_user_func($callable));
        $this->assertEquals($expectedPointer, $instructionPointer);
    }
}
