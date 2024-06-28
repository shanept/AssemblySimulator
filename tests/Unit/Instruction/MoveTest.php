<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\Move;

/**
 * @covers shanept\AssemblySimulator\Instruction\Move
 */
class MoveTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    /**
     * @return array<int, array{int, int, int, string, int, string, RegisterObj, int}>
     */
    public static function movBxLoadsImmediateValueDataProvider(): array
    {
        return [
            [Simulator::LONG_MODE, 0, 0, "\xB0", 3, "\x44", Register::AL, 0x44],
            [Simulator::LONG_MODE, 0, 0, "\xB1", 3, "\x30", Register::CL, 0x30],
            [Simulator::LONG_MODE, 0, 0, "\xB2", 3, "\x14", Register::DL, 0x14],
            [Simulator::LONG_MODE, 0, 0, "\xB3", 3, "\x70", Register::BL, 0x70],
            [Simulator::LONG_MODE, 0, 0, "\xB4", 3, "\xF3", Register::AH, 0xF3],
            [Simulator::LONG_MODE, 0, 0, "\xB5", 3, "\x70", Register::CH, 0x70],
            [Simulator::LONG_MODE, 0, 0, "\xB6", 3, "\x51", Register::DH, 0x51],
            [Simulator::LONG_MODE, 0, 0, "\xB7", 3, "\x93", Register::BH, 0x93],
            [Simulator::LONG_MODE, 0x48, 0, "\xB0", 3, "\x30", Register::AL, 0x30],
            [Simulator::LONG_MODE, 0x48, 0, "\xB1", 3, "\x14", Register::CL, 0x14],
            [Simulator::LONG_MODE, 0x48, 0, "\xB2", 3, "\x70", Register::DL, 0x70],
            [Simulator::LONG_MODE, 0x48, 0, "\xB3", 3, "\xF3", Register::BL, 0xF3],
            [Simulator::LONG_MODE, 0x48, 0, "\xB4", 3, "\x70", Register::SPL, 0x70],
            [Simulator::LONG_MODE, 0x48, 0, "\xB5", 3, "\x51", Register::BPL, 0x51],
            [Simulator::LONG_MODE, 0x48, 0, "\xB6", 3, "\x93", Register::SIL, 0x93],
            [Simulator::LONG_MODE, 0x48, 0, "\xB7", 3, "\x20", Register::DIL, 0x20],

            [Simulator::LONG_MODE, 0, 0x66, "\xB8", 3, "\x01\x20", Register::AX, 0x2001],
            [Simulator::LONG_MODE, 0, 0x66, "\xB9", 3, "\x01\x30", Register::CX, 0x3001],
            [Simulator::LONG_MODE, 0, 0x66, "\xBA", 3, "\x01\x14", Register::DX, 0x1401],
            [Simulator::LONG_MODE, 0, 0x66, "\xBB", 3, "\x01\x70", Register::BX, 0x7001],
            [Simulator::LONG_MODE, 0, 0x66, "\xBC", 3, "\x01\xF3", Register::SP, 0xF301],
            [Simulator::LONG_MODE, 0, 0x66, "\xBD", 3, "\x71\x70", Register::BP, 0x7071],
            [Simulator::LONG_MODE, 0, 0x66, "\xBE", 3, "\x50\x51", Register::SI, 0x5150],
            [Simulator::LONG_MODE, 0, 0x66, "\xBF", 3, "\x01\x93", Register::DI, 0x9301],
            [Simulator::LONG_MODE, 0, 0x66, "\xB8", 3, "\x01\x20", Register::AX, 0x2001],
            [Simulator::LONG_MODE, 0, 0, "\xB9", 3, "\x01\x00\x20\x00", Register::ECX, 0x200001],
            [Simulator::LONG_MODE, 0, 0, "\xBA", 3, "\x01\x04\x20\x00", Register::EDX, 0x200401],
            [Simulator::LONG_MODE, 0, 0, "\xBB", 3, "\x01\x00\x20\x00", Register::EBX, 0x200001],
            [Simulator::LONG_MODE, 0, 0, "\xBC", 3, "\x01\x03\x20\x00", Register::ESP, 0x200301],
            [Simulator::LONG_MODE, 0, 0, "\xBD", 3, "\x71\x00\x20\x00", Register::EBP, 0x200071],
            [Simulator::LONG_MODE, 0, 0, "\xBE", 3, "\x01\x00\x20\x00", Register::ESI, 0x200001],
            [Simulator::LONG_MODE, 0, 0, "\xBF", 3, "\x01\x00\x20\x10", Register::EDI, 0x10200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xB8", 3, "\x01\x20\x20\x00\x00\x00\x00\x00", Register::RAX, 0x202001],
            [Simulator::LONG_MODE, 0x48, 0, "\xB9", 3, "\x01\x00\x20\x00\x00\x00\x00\x00", Register::RCX, 0x200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xBA", 3, "\x01\x04\x20\x00\x00\x00\x00\x00", Register::RDX, 0x200401],
            [Simulator::LONG_MODE, 0x48, 0, "\xBB", 3, "\x01\x00\x20\x00\x00\x00\x00\x00", Register::RBX, 0x200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xBC", 3, "\x01\x03\x20\x00\x00\x00\x00\x00", Register::RSP, 0x200301],
            [Simulator::LONG_MODE, 0x48, 0, "\xBD", 3, "\x71\x00\x20\x00\x00\x00\x00\x00", Register::RBP, 0x200071],
            [Simulator::LONG_MODE, 0x48, 0, "\xBE", 3, "\x01\x00\x20\x00\x00\x00\x00\x00", Register::RSI, 0x200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xBF", 3, "\x01\x00\x20\x10\x00\x00\x00\x00", Register::RDI, 0x10200001],
        ];
    }

    /**
     * @dataProvider movBxLoadsImmediateValueDataProvider
     * @small
     *
     * @param RegisterObj $register
     */
    public function testMovBxLoadsImmediateValue(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        int $instructionPointer,
        string $address,
        array $register,
        int $expected
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        // mov ecx,0x200001
        // 0xB9 0x01 0x00 0x20 0x00
        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue): bool {
                      return $requested === $prefixValue;
                  });

        $values = [$address, $opcode];

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturnCallback(function ($length) use (&$values) {
                      $value = array_pop($values);

                      if (is_null($value)) {
                          $this->fail('Out of values.');
                      }

                      if ($length !== strlen($value)) {
                          $message = sprintf(
                              'Expected string of length %d, received "%s".',
                              $length,
                              bin2hex($value),
                          );

                          $this->fail($message);
                      }

                      return $value;
                  });

        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$instructionPointer): void {
                      $instructionPointer += $amount;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturnCallback(function () use ($instructionPointer) {
                      return $instructionPointer;
                  });

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($register, $expected);

        $expectedPointer = $instructionPointer + 1 + strlen($address);

        $move = new Move();
        $move->setSimulator($simulator);

        $functionName = (ord($opcode) > 0xB7 ? 'executeOperandBx' : 'executeOperandBx8');

        $this->assertTrue(call_user_func([$move, $functionName]));
        $this->assertEquals($expectedPointer, $instructionPointer, 'Failed Instruction Pointer');
    }

    /**
     * @return array<int, array{int, int, int, string, int, string, RegisterObj, RegisterObj, int}>
     */
    public static function movRegisterToRegister(): array
    {
        return [
            [Simulator::LONG_MODE, 0x00, 0x00, "\x88", 2, "\xD1", Register::DL, Register::CL, 432],
            [Simulator::LONG_MODE, 0x40, 0x00, "\x88", 2, "\xF7", Register::SIL, Register::DIL, 432],
            [Simulator::LONG_MODE, 0x45, 0x00, "\x88", 5, "\xD1", Register::R10B, Register::R9B, 691],

            [Simulator::LONG_MODE, 0x48, 0x00, "\x89", 7, "\xD1", Register::RDX, Register::RCX, 790],
            [Simulator::LONG_MODE, 0x49, 0x00, "\x89", 3, "\xFC", Register::RDI, Register::R12, 133],
            [Simulator::LONG_MODE, 0x4D, 0x00, "\x89", 4, "\xD1", Register::R10, Register::R9, 581],

            [Simulator::LONG_MODE, 0x00, 0x00, "\x8A", 2, "\xD1", Register::CL, Register::DL, 432],
            [Simulator::LONG_MODE, 0x40, 0x00, "\x8A", 2, "\xF7", Register::DIL, Register::SIL, 432],
            [Simulator::LONG_MODE, 0x45, 0x00, "\x8A", 5, "\xD1", Register::R9B, Register::R10B, 691],

            [Simulator::LONG_MODE, 0x48, 0x00, "\x8B", 7, "\xD1", Register::RCX, Register::RDX, 790],
            [Simulator::LONG_MODE, 0x49, 0x00, "\x8B", 3, "\xFC", Register::R12, Register::RDI, 133],
            [Simulator::LONG_MODE, 0x4D, 0x00, "\x8B", 4, "\xD1", Register::R9, Register::R10, 581],
        ];
    }

    /**
     * @dataProvider movRegisterToRegister
     * @small
     *
     * @param RegisterObj $readRegister
     * @param RegisterObj $writeRegister
     */
    public function testMovRegisterToRegister(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        int $instructionPointer,
        string $modRmByte,
        array $readRegister,
        array $writeRegister,
        int $expectedValue
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue): bool {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->once())
                  ->method('readRegister')
                  ->willReturn($expectedValue)
                  ->with($readRegister);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($writeRegister, $expectedValue);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstructionPointer')
                  ->willReturn($modRmByte);

        $simulator->method('getInstructionPointer')
                  ->willReturnCallback(function () use ($instructionPointer) {
                      return $instructionPointer;
                  });

        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$instructionPointer) {
                      $instructionPointer += $amount;
                  });

        $expectedPointer = $instructionPointer + 2;

        $instruction = new Move();
        $instruction->setSimulator($simulator);

        $functionName = sprintf('executeOperand%02X', ord($opcode));
        $callable = [$instruction, $functionName];

        if (! is_callable($callable)) {
            $this->fail("Method Move::{$functionName} does not exist.");
        }

        $this->assertTrue(call_user_func($callable));
        $this->assertEquals($expectedPointer, $instructionPointer);
    }

    /**
     * @return array<int, array{int, int, int, string, int, string, string, RegisterObj, int, RegisterObj, int, string}>
     */
    public static function movRegisterToSibValue(): array
    {
        return [
            [Simulator::LONG_MODE, 0x00, 0x67, "\x88", 3, "\x74", "\x24", Register::RSP, 0x00, Register::ESP, 123, "\x10"],
            [Simulator::LONG_MODE, 0x00, 0x00, "\x88", 3, "\x74", "\x24", Register::RSP, 0x00, Register::RSP, 123, "\x10"],
            [Simulator::LONG_MODE, 0x49, 0x67, "\x88", 3, "\x74", "\x24", Register::RSP, 0x00, Register::R12D, 123, "\x10"],
            [Simulator::LONG_MODE, 0x49, 0x00, "\x88", 3, "\x74", "\x24", Register::RSP, 0x00, Register::R12, 123, "\x10"],

            [Simulator::LONG_MODE, 0x00, 0x67, "\x89", 3, "\x74", "\x24", Register::RSP, 0x00, Register::ESP, 123, "\x10"],
            [Simulator::LONG_MODE, 0x00, 0x00, "\x89", 3, "\x74", "\x24", Register::RSP, 0x00, Register::RSP, 123, "\x10"],
            [Simulator::LONG_MODE, 0x49, 0x67, "\x89", 3, "\x74", "\x24", Register::RSP, 0x00, Register::R12D, 123, "\x10"],
            [Simulator::LONG_MODE, 0x49, 0x00, "\x89", 3, "\x74", "\x24", Register::RSP, 0x00, Register::R12, 123, "\x10"],

            [Simulator::LONG_MODE, 0x00, 0x67, "\x8A", 3, "\x74", "\x24", Register::RSP, 0x00, Register::ESP, 123, "\x10"],
            [Simulator::LONG_MODE, 0x00, 0x00, "\x8A", 3, "\x74", "\x24", Register::RSP, 0x00, Register::RSP, 123, "\x10"],
            [Simulator::LONG_MODE, 0x49, 0x67, "\x8A", 3, "\x74", "\x24", Register::RSP, 0x00, Register::R12D, 123, "\x10"],
            [Simulator::LONG_MODE, 0x49, 0x00, "\x8A", 3, "\x74", "\x24", Register::RSP, 0x00, Register::R12, 123, "\x10"],

            [Simulator::LONG_MODE, 0x00, 0x67, "\x8B", 3, "\x74", "\x24", Register::RSP, 0x00, Register::ESP, 123, "\x10"],
            [Simulator::LONG_MODE, 0x00, 0x00, "\x8B", 3, "\x74", "\x24", Register::RSP, 0x00, Register::RSP, 123, "\x10"],
            [Simulator::LONG_MODE, 0x49, 0x67, "\x8B", 3, "\x74", "\x24", Register::RSP, 0x00, Register::R12D, 123, "\x10"],
            [Simulator::LONG_MODE, 0x49, 0x00, "\x8B", 3, "\x74", "\x24", Register::RSP, 0x00, Register::R12, 123, "\x10"],
        ];
    }

    /**
     * We aren't looking for much here as we don't actually handle the value.
     * We just want to ensure we move past the SIB value without error and
     * return true.
     *
     * @dataProvider movRegisterToSibValue
     * @small
     *
     * @param RegisterObj $indexRegister
     * @param RegisterObj $baseRegister
     */
    public function testMovRegisterToSibValue(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        int $instructionPointer,
        string $modRmByte,
        string $sibByte,
        array $indexRegister,
        int $indexValue,
        array $baseRegister,
        int $baseValue,
        string $displacementBytes
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);
        $this->mockInstructionPointer($simulator, $instructionPointer);

        // mov [esp+0x10],rsi
        // 0x88 0x74 0x24 0x10
        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue): bool {
                      return $requested === $prefixValue;
                  });

        $values = [
            $sibByte,
            $modRmByte,
        ];

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturnCallback(function ($length) use (&$values) {
                      $value = array_pop($values);

                      if (is_null($value)) {
                          $this->fail('Out of values.');
                      }

                      if ($length !== strlen($value)) {
                          $message = sprintf(
                              'Expected string of length %d, received "%s".',
                              $length,
                              $value,
                          );

                          $this->fail($message);
                      }

                      return $value;
                  });

        $dispSize = strlen($displacementBytes);

        if ($dispSize) {
            $simulator->method('getCodeBuffer')
                      ->willReturn($displacementBytes)
                      ->with($instructionPointer + 3, $dispSize);
        }

        // If the index is *SP, we have no index.
        if (4 === $indexRegister['offset']) {
            $indexRegister = null;
        }

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register) use (
                      $indexRegister,
                      $indexValue,
                      $baseRegister,
                      $baseValue
                  ) {
                      switch ($register) {
                          case $indexRegister:
                              return $indexValue;
                          case $baseRegister:
                              return $baseValue;
                      }

                      $this->fail(sprintf('Unexpected register %s.', $register['name']));
                  });

        $expectedPointer = $instructionPointer + 4;

        $instruction = new Move();
        $instruction->setSimulator($simulator);

        $functionName = sprintf('executeOperand%02X', ord($opcode));
        $callable = [$instruction, $functionName];

        if (! is_callable($callable)) {
            $this->fail("Method Move::{$functionName} does not exist.");
        }

        $this->assertTrue(call_user_func($callable));
        $this->assertEquals($expectedPointer, $instructionPointer);
    }

    /**
     * @return array<string, array{int, int, int, string, int, string, RegisterObj, string, int}>
     */
    public static function movImmediateToModRmRegisterDataProvider(): array
    {
        return [
            // Parameter Encoding MI
            'Test C6 to DH' => [Simulator::LONG_MODE, 0x00, 0x00, "\xC6", 3, "\xC6", Register::DH, "\x04", 4],
            'Test C6 to SIL' => [Simulator::LONG_MODE, 0x40, 0x00, "\xC6", 3, "\xC6", Register::SIL, "\x04", 4],
            'Test C6 to R14B' => [Simulator::LONG_MODE, 0x41, 0x00, "\xC6", 3, "\xC6", Register::R14B, "\x04", 4],
            'Test C7 to EAX' => [Simulator::LONG_MODE, 0x00, 0x00, "\xC7", 3, "\xC0", Register::EAX, "\x04\x04\x04\x00", 0x40404],
            'Test C7 to AX' => [Simulator::LONG_MODE, 0x00, 0x66, "\xC7", 3, "\xC0", Register::AX, "\x04\x05", 0x504],
            'Test Rex.W C7 to RAX' => [
                Simulator::LONG_MODE,
                0x48,
                0x66,
                "\xC7",
                3,
                "\xC0",
                Register::RAX,
                "\x04\x05\xF3\x42\x04\x05\xF3\x42",
                0x42F3050442F30504,
            ],
            'Test 0x66 Rex.B C7 to R8D' => [
                Simulator::LONG_MODE,
                0x41,
                0x00,
                "\xC7",
                1,
                "\xC0",
                Register::R8D,
                "\x04\x05\xF3\x42",
                0x42F30504,
            ],
            'Test Rex.WB C7 to R8' => [
                Simulator::LONG_MODE,
                0x49,
                0x00,
                "\xC7",
                5,
                "\xC0",
                Register::R8,
                "\x04\x05\xF3\x42\x04\x05\xF3\x42",
                0x42F3050442F30504,
            ],
        ];
    }

    /**
     * @dataProvider movImmediateToModRmRegisterDataProvider
     * @small
     *
     * @param RegisterObj $writeRegister
     */
    public function testMovImmediateToModRmRegister(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        int $instructionPointer,
        string $modRmByte,
        array $writeRegister,
        string $binImmediate,
        int $intImmediate
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($writeRegister, $intImmediate);

        $values = [
            $binImmediate,
            $modRmByte,
        ];

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturnCallback(function ($length) use (&$values): string {
                      $value = array_pop($values);

                      if (is_null($value)) {
                          $this->fail('Out of values.');
                      }

                      if ($length !== strlen($value)) {
                          $message = sprintf(
                              'Expected string of length %d, received "%s".',
                              $length,
                              $value,
                          );

                          $this->fail($message);
                      }

                      return $value;
                  });

        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$instructionPointer): void {
                      $instructionPointer += $amount;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturnCallback(function () use ($instructionPointer) {
                      return $instructionPointer;
                  });

        $expectedPointer = $instructionPointer + 2 + strlen($binImmediate);

        $instruction = new Move();
        $instruction->setSimulator($simulator);

        $functionName = sprintf('executeOperand%02X', ord($opcode));
        $callable = [$instruction, $functionName];

        if (! is_callable($callable)) {
            $this->fail("Method Move::{$functionName} does not exist.");
        }

        $this->assertTrue(call_user_func($callable));
        $this->assertEquals($expectedPointer, $instructionPointer);
    }

    /**
     * @return array<string, array{int, int, int, string, int, RegisterObj, string, int}>
     */
    public static function movImmediateToOperandRegisterDataProvider(): array
    {
        return [
            // Parameter Encoding OI
            'Test B0 to AL' => [Simulator::LONG_MODE, 0x40, 0x00, "\xB0", 3, Register::AL, "\x04", 4],
            'Test B0 (Rex) to AL' => [Simulator::LONG_MODE, 0x00, 0x00, "\xB0", 3, Register::AL, "\x04", 4],
            'Test B1 to CL' => [Simulator::LONG_MODE, 0x00, 0x00, "\xB1", 3, Register::CL, "\x04", 4],
            'Test B1 (Rex) to CL' => [Simulator::LONG_MODE, 0x40, 0x00, "\xB1", 3, Register::CL, "\x04", 4],
            'Test B2 to DL' => [Simulator::LONG_MODE, 0x00, 0x00, "\xB2", 3, Register::DL, "\x04", 4],
            'Test B2 (Rex) to DL' => [Simulator::LONG_MODE, 0x40, 0x00, "\xB2", 3, Register::DL, "\x04", 4],
            'Test B3 to DL' => [Simulator::LONG_MODE, 0x00, 0x00, "\xB3", 3, Register::BL, "\x04", 4],
            'Test B3 (Rex) to DL' => [Simulator::LONG_MODE, 0x40, 0x00, "\xB3", 3, Register::BL, "\x04", 4],
            'Test B4 to AH' => [Simulator::LONG_MODE, 0x00, 0x00, "\xB4", 3, Register::AH, "\x04", 4],
            'Test B4 (Rex) to SPL' => [Simulator::LONG_MODE, 0x40, 0x00, "\xB4", 3, Register::SPL, "\x04", 4],
            'Test B5 to CH' => [Simulator::LONG_MODE, 0x00, 0x00, "\xB5", 3, Register::CH, "\x04", 4],
            'Test B5 (Rex) to SPL' => [Simulator::LONG_MODE, 0x40, 0x00, "\xB5", 3, Register::BPL, "\x04", 4],
            'Test B6 to DH' => [Simulator::LONG_MODE, 0x00, 0x00, "\xB6", 3, Register::DH, "\x04", 4],
            'Test B6 (Rex) to SPL' => [Simulator::LONG_MODE, 0x40, 0x00, "\xB6", 3, Register::SIL, "\x04", 4],
            'Test B7 to BH' => [Simulator::LONG_MODE, 0x00, 0x00, "\xB7", 3, Register::BH, "\x04", 4],
            'Test B7 (Rex) to SPL' => [Simulator::LONG_MODE, 0x40, 0x00, "\xB7", 3, Register::DIL, "\x04", 4],
        ];
    }

    /**
     * @dataProvider movImmediateToOperandRegisterDataProvider
     * @small
     *
     * @param RegisterObj $writeRegister
     */
    public function testMovImmediateToOperandRegister(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        int $instructionPointer,
        array $writeRegister,
        string $binImmediate,
        int $intImmediate
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($writeRegister, $intImmediate);

        $values = [
            $binImmediate,
            $opcode,
        ];

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturnCallback(function ($length) use (&$values): string {
                      $value = array_pop($values);

                      if (is_null($value)) {
                          $this->fail('Ran out of values to return.');
                      }

                      return $value;
                  })->with(1);


        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$instructionPointer): void {
                      $instructionPointer += $amount;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturnCallback(function () use ($instructionPointer) {
                      return $instructionPointer;
                  });

        $expectedPointer = $instructionPointer + 1 + strlen($binImmediate);

        $instruction = new Move();
        $instruction->setSimulator($simulator);

        $functionName = ord($opcode) < 0xB8 ? 'executeOperandBx8' : 'executeOperandBx';

        $this->assertTrue(call_user_func([$instruction, $functionName]), 'Instruction returned false.');
        $this->assertEquals($expectedPointer, $instructionPointer, 'Failed on Instruction Pointer');
    }

    /**
     * @return array<int, array{int, int, int, string, int, string, string, string}>
     */
    public static function movImmediateToRipAddressDataProvider(): array
    {
        return [
            // Rip address
            [Simulator::LONG_MODE, 0x00, 0x00, "\xC6", 3, "\x05", "\x84\x57\x32\x00", "\x01"],

            // Rip address
            [Simulator::LONG_MODE, 0x00, 0x66, "\xC7", 3, "\x05", "\x84\x57\x32\x00", "\x01\x02"],
            [Simulator::LONG_MODE, 0x00, 0x00, "\xC7", 3, "\x05", "\x84\x57\x32\x00", "\x01\x02\x03\x04"],
            [Simulator::LONG_MODE, 0x48, 0x00, "\xC7", 3, "\x05", "\x84\x57\x32\x00", "\x01\x02\x03\x04\x01\x02\x03\x04"],
        ];
    }

    /**
     * @dataProvider movImmediateToRipAddressDataProvider
     * @small
     */
    public function testMovImmediateToRipAddress(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        int $instructionPointer,
        string $modRmByte,
        string $destinationAddress,
        string $binImmediate
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $values = [
            $binImmediate,
            $destinationAddress,
            $modRmByte,
        ];

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturnCallback(function ($length) use (&$values): string {
                      $value = array_pop($values);

                      if (is_null($value)) {
                          $this->fail('Out of values.');
                      }

                      if ($length !== strlen($value)) {
                          $message = sprintf(
                              'Expected string of length %d, received "%s".',
                              $length,
                              $value,
                          );

                          $this->fail($message);
                      }

                      return $value;
                  });

        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$instructionPointer): void {
                      $instructionPointer += $amount;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturnCallback(function () use ($instructionPointer) {
                      return $instructionPointer;
                  });

        $expectedPointer = $instructionPointer + 6 + strlen($binImmediate);

        $instruction = new Move();
        $instruction->setSimulator($simulator);

        $functionName = sprintf('executeOperand%02X', ord($opcode));
        $callable = [$instruction, $functionName];

        if (! is_callable($callable)) {
            $this->fail("Method Move::{$functionName} does not exist.");
        }

        $this->assertTrue(call_user_func($callable));
        $this->assertEquals($expectedPointer, $instructionPointer);
    }
}
