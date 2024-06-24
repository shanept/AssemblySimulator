<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Flags;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\ExclusiveOr;

/**
 * @covers shanept\AssemblySimulator\Instruction\ExclusiveOr
 */
class ExclusiveOrTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    /**
     * @return array<string, array{int, int, int, string, int, RegisterObj, int, string}>
     */
    public static function xorRegisterAgainstItselfIsEmptyDataProvider(): array
    {
        return [
            'Xor(30) on Long Mode with Rex.RB)' => [Simulator::LONG_MODE, 0x45, 0, "\x30", 7, Register::R9B, 1234, "\xC9"],
            'Xor(30) on Long Mode with Rex.WRB)' => [Simulator::LONG_MODE, 0x4D, 0, "\x30", 7, Register::R9B, 1234, "\xC9"],
            'Xor(31) on Long Mode with Rex.RB)' => [Simulator::LONG_MODE, 0x45, 0, "\x31", 7, Register::R9D, 1234, "\xC9"],
            'Xor(31) on Long Mode with Rex.WRB)' => [Simulator::LONG_MODE, 0x4D, 0, "\x31", 7, Register::R9, 1234, "\xC9"],
            'Xor(31) on Long Mode with Operand Prefix, Rex.RB)' =>
                [Simulator::LONG_MODE, 0x45, 0x66, "\x31", 7, Register::R9W, 1234, "\xC9"],
            'Xor(32) on Long Mode with Rex.RB)' => [Simulator::LONG_MODE, 0x45, 0, "\x32", 7, Register::R9B, 1234, "\xC9"],
            'Xor(32) on Long Mode with Rex.WRB)' => [Simulator::LONG_MODE, 0x4D, 0, "\x32", 7, Register::R9B, 1234, "\xC9"],
            'Xor(33) on Long Mode with Rex.RB)' => [Simulator::LONG_MODE, 0x45, 0, "\x33", 7, Register::R9D, 1234, "\xC9"],
            'Xor(33) on Long Mode with Rex.WRB)' => [Simulator::LONG_MODE, 0x4D, 0, "\x33", 7, Register::R9, 1234, "\xC9"],
            'Xor(33) on Long Mode with Operand Prefix, Rex.RB)' =>
                [Simulator::LONG_MODE, 0x45, 0x66, "\x33", 7, Register::R9W, 1234, "\xC9"],
        ];
    }

    /**
     * @dataProvider xorRegisterAgainstItselfIsEmptyDataProvider
     * @small
     *
     * @param RegisterObj $register
     */
    public function testXorRegisterAgainstItselfIsEmpty(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        int $instructionPointer,
        array $register,
        int $regValue,
        string $instruction,
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);
        $this->mockInstructionPointer($simulator, $instructionPointer);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->exactly(2))
                  ->method('readRegister')
                  ->willReturn($regValue)
                  ->with($register);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($register, 0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($instruction)
                  ->with(1);

        $expectedPointer = $instructionPointer + 2;

        $instruction = new ExclusiveOr();
        $instruction->setSimulator($simulator);

        $functionName = sprintf('executeOperand%02X', ord($opcode));
        $callable = [$instruction, $functionName];

        if (! is_callable($callable)) {
            $this->fail("Method ExclusiveOr::{$functionName} does not exist.");
        }

        $this->assertTrue(call_user_func($callable));
        $this->assertEquals($expectedPointer, $instructionPointer);
    }

    /**
     * @return array<int, array{int, int, int, RegisterObj, int, RegisterObj, int, string}>
     */
    public static function xorWithTwoRegistersDataProvider(): array
    {
        return [
            [Simulator::LONG_MODE, 0x40, 0, "\x30", 42, Register::AL, 225, Register::CL, 210, "\xC8", Register::AL, 51],
            [Simulator::LONG_MODE, 0x40, 0, "\x30", 42, Register::AL, 158, Register::CL, 175, "\xC8", Register::AL, 49],
            [Simulator::LONG_MODE, 0x45, 0, "\x30", 42, Register::R8B, 225, Register::R9B, 210, "\xC8", Register::R8B, 51],
            [Simulator::LONG_MODE, 0x4D, 0, "\x30", 42, Register::R8B, 158, Register::R9B, 175, "\xC8", Register::R8B, 49],

            [Simulator::LONG_MODE, 0x45, 0, "\x31", 42, Register::R8D, 4321, Register::R9D, 1234, "\xC8", Register::R8D, 5171],
            [Simulator::LONG_MODE, 0x4D, 0, "\x31", 42, Register::R8, 9374, Register::R9, 4527, "\xC8", Register::R8, 13617],
            [Simulator::LONG_MODE, 0x45, 0x66, "\x31", 42, Register::R8W, 9374, Register::R9W, 4527, "\xC8", Register::R8W, 13617],

            [Simulator::LONG_MODE, 0x40, 0, "\x32", 42, Register::CL, 225, Register::AL, 210, "\xC8", Register::CL, 51],
            [Simulator::LONG_MODE, 0x40, 0, "\x32", 42, Register::CL, 158, Register::AL, 175, "\xC8", Register::CL, 49],
            [Simulator::LONG_MODE, 0x45, 0, "\x32", 42, Register::R9B, 225, Register::R8B, 210, "\xC8", Register::R9B, 51],
            [Simulator::LONG_MODE, 0x4D, 0, "\x32", 42, Register::R9B, 158, Register::R8B, 175, "\xC8", Register::R9B, 49],

            [Simulator::LONG_MODE, 0x45, 0, "\x33", 42, Register::R9D, 4321, Register::R8D, 1234, "\xC8", Register::R9D, 5171],
            [Simulator::LONG_MODE, 0x4D, 0, "\x33", 42, Register::R9, 9374, Register::R8, 4527, "\xC8", Register::R9, 13617],
            [Simulator::LONG_MODE, 0x45, 0x66, "\x33", 42, Register::R9W, 9374, Register::R8W, 4527, "\xC8", Register::R9W, 13617],
        ];
    }

    /**
     * @dataProvider xorWithTwoRegistersDataProvider
     * @small
     *
     * @param RegisterObj $readRegisterOne
     * @param RegisterObj $readRegisterTwo
     * @param RegisterObj $writeRegister
     */
    public function testXorWithTwoRegisters(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        int $instructionPointer,
        array $readRegisterOne,
        int $readValueOne,
        array $readRegisterTwo,
        int $readValueTwo,
        string $modRmByte,
        array $writeRegister,
        int $writeValue,
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);
        $this->mockInstructionPointer($simulator, $instructionPointer);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->exactly(2))
                  ->method('readRegister')
                  ->willReturnCallback(function ($register) use (
                      $readRegisterOne,
                      $readValueOne,
                      $readRegisterTwo,
                      $readValueTwo,
                  ) {
                      if ($register === $readRegisterOne) {
                          return $readValueOne;
                      } elseif ($register === $readRegisterTwo) {
                          return $readValueTwo;
                      }

                      $this->fail('Unknown register ' . $register['name']);
                  });

        $expectedPointer = $instructionPointer + 2;

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($writeRegister, $writeValue);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstruction')
                  ->willReturn($modRmByte)
                  ->with(1);

        $instruction = new ExclusiveOr();
        $instruction->setSimulator($simulator);

        $functionName = sprintf('executeOperand%02X', ord($opcode));
        $callable = [$instruction, $functionName];

        if (! is_callable($callable)) {
            $this->fail("Method ExclusiveOr::{$functionName} does not exist.");
        }

        $this->assertTrue(call_user_func($callable));
        $this->assertEquals($expectedPointer, $instructionPointer);
    }

    /**
     * @return array<int, array{int, int, int, string, string, RegisterObj, int, RegisterObj, int, bool, bool, bool, bool, bool}>
     */
    public static function xorSetsFlagsDataProvider(): array
    {
        $t = true;
        $f = false;

        return [
            [Simulator::LONG_MODE, 0x45, 0x00, "\x30", "\xC9", Register::R9B, 0, Register::R9B, 0, $f, $t, $t, $f, $f],
            [Simulator::LONG_MODE, 0x4D, 0x00, "\x31", "\xC9", Register::R9, 0, Register::R9, 0, $f, $t, $t, $f, $f],
            [Simulator::LONG_MODE, 0x45, 0x00, "\x32", "\xC9", Register::R9B, 0, Register::R9B, 0, $f, $t, $t, $f, $f],
            [Simulator::LONG_MODE, 0x4D, 0x00, "\x33", "\xC9", Register::R9, 0, Register::R9, 0, $f, $t, $t, $f, $f],

            [Simulator::LONG_MODE, 0x45, 0x00, "\x30", "\xC8", Register::R9B, 1, Register::R8B, 0, $f, $f, $f, $f, $f],
            [Simulator::LONG_MODE, 0x4D, 0x00, "\x31", "\xC8", Register::R9, 1, Register::R8, 0, $f, $f, $f, $f, $f],
            [Simulator::LONG_MODE, 0x45, 0x00, "\x32", "\xC8", Register::R9B, 1, Register::R8B, 0, $f, $f, $f, $f, $f],
            [Simulator::LONG_MODE, 0x4D, 0x00, "\x33", "\xC8", Register::R9, 1, Register::R8, 0, $f, $f, $f, $f, $f],
        ];
    }

    /**
     * @dataProvider xorSetsFlagsDataProvider
     * @small
     *
     * @param RegisterObj $readRegisterOne
     * @param RegisterObj $readRegisterTwo
     */
    public function testXorSetsFlagsAfterExecution(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        string $modRmByte,
        array $readRegisterOne,
        int $readValueOne,
        array $readRegisterTwo,
        int $readValueTwo,
        bool $carryFlag,
        bool $parityFlag,
        bool $zeroFlag,
        bool $signFlag,
        bool $overflowFlag,
    ): void {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $flagVerifier = function ($flag, $value) use (
            $carryFlag,
            $parityFlag,
            $zeroFlag,
            $signFlag,
            $overflowFlag,
        ): void {
            $expected = [
                Flags::OF => $overflowFlag,
                Flags::CF => $carryFlag,
                Flags::SF => $signFlag,
                Flags::ZF => $zeroFlag,
                Flags::PF => $parityFlag,
            ];

            $name = '<UNKNOWN>';

            switch ($flag) {
                case Flags::OF:
                    $name = 'OF';
                    break;
                case Flags::CF:
                    $name = 'CF';
                    break;
                case Flags::SF:
                    $name = 'SF';
                    break;
                case Flags::ZF:
                    $name = 'ZF';
                    break;
                case Flags::PF:
                    $name = 'PF';
                    break;
            }

            $message = sprintf('Assertion for setFlags on Flags::%s:', $name);
            $this->assertEquals($expected[$flag], $value, $message);
        };

        $simulator->expects($this->exactly(5))
                  ->method('setFlag')
                  ->willReturnCallback($flagVerifier);

        // REX.RB xor r9 r9
        // 0x45 0x31 0xC9
        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue): bool {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->exactly(2))
                  ->method('readRegister')
                  ->willReturnCallback(function ($register) use (
                      $readRegisterOne,
                      $readValueOne,
                      $readRegisterTwo,
                      $readValueTwo,
                  ) {
                      if ($register === $readRegisterOne) {
                          return $readValueOne;
                      } elseif ($register === $readRegisterTwo) {
                          return $readValueTwo;
                      }

                      $this->fail('Unknown register ' . $register['name']);
                  });

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($modRmByte)
                  ->with(1);

        $instruction = new ExclusiveOr();
        $instruction->setSimulator($simulator);

        $functionName = sprintf('executeOperand%02X', ord($opcode));
        $callable = [$instruction, $functionName];

        if (! is_callable($callable)) {
            $this->fail("Method ExclusiveOr::{$functionName} does not exist.");
        }

        $this->assertTrue(call_user_func($callable));
    }

    /**
     * @return array<int, array{string}>
     */
    public static function xorOperandsDataProvider(): array
    {
        return [
            ["\x30"],
            ["\x31"],
            ["\x32"],
            ["\x33"],
        ];
    }

    /**
     * @dataProvider xorOperandsDataProvider
     * @small
     */
    public function testXorWithIncorrectModByteFails(string $opcode): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // REX.RB xor r9d,r9d, (mod 2 - incorrect)
        // 0x45 0x31 0x89 (mod 2 - incorrect)
        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstruction')
                  ->willReturn("\x89")
                  ->with(1);

        $instruction = new ExclusiveOr();
        $instruction->setSimulator($simulator);

        $functionName = sprintf('executeOperand%02X', ord($opcode));
        $callable = [$instruction, $functionName];

        if (! is_callable($callable)) {
            $this->fail("Method ExclusiveOr::{$functionName} does not exist.");
        }

        $this->expectException(\RuntimeException::class);
        call_user_func($callable);
    }
}
