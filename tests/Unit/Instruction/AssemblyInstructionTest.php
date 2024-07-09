<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use ReflectionClass;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Address\RipAddress;
use shanept\AssemblySimulator\Address\SibAddress;
use shanept\AssemblySimulator\Address\ModRmAddress;
use shanept\AssemblySimulatorTests\Fakes\TestAssemblyInstruction;

class AssemblyInstructionTest extends TestCase
{
    use MockSimulatorTrait;

    /**
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction
     */
    public function testSetSimulatorAttemptsToRegisterInstruction(): void
    {
        $simulator = $this->getMockSimulator();
        $instruction = new TestAssemblyInstruction();

        $simulator->expects($this->once())
                  ->method('registerInstructions')
                  ->with($instruction, []);

        $instruction->setSimulator($simulator);
    }

    /**
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction
     */
    public function testGetSimulatorReturnsSetSimulator(): void
    {
        $simulator = $this->getMockSimulator();
        $instruction = new TestAssemblyInstruction();

        $instruction->setSimulator($simulator);

        $getSimulator = new ReflectionMethod($instruction, 'getSimulator');
        $getSimulator->setAccessible(true);

        $this->assertEquals($simulator, $getSimulator->invoke($instruction));
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function protectedMethodsDataProvider(): array
    {
        return [
            ['getSimulator'],
            ['getOperandSize'],
            ['getAddressSize'],
            ['parseModRmByte'],
            ['unpackImmediate'],
            ['packImmediate'],
            ['parseAddress'],
            ['getStackPointerRegister'],
        ];
    }

    /**
     * It is important that these methods are not private, as they must be
     * accessible to child functions.
     *
     * @dataProvider protectedMethodsDataProvider
     * @small
     */
    public function testProtectedMethodsVisibility(
        string $methodName
    ): void {
        $instruction = new TestAssemblyInstruction();

        $method = new ReflectionMethod($instruction, $methodName);

        $this->assertTrue($method->isProtected());
    }

    /**
     * @small
     */
    public function testNoExtraProtectedMethods(): void
    {
        $expected = array_column(self::protectedMethodsDataProvider(), 0);

        $instruction = new TestAssemblyInstruction();

        $class = new ReflectionClass($instruction);
        $protectedMethods = $class->getMethods(ReflectionMethod::IS_PROTECTED);

        $protectedMethods = array_map(function ($method) {
            return $method->getName();
        }, $protectedMethods);

        $this->assertEqualsCanonicalizing($expected, $protectedMethods);
    }

    /**
     * @return array<string, array{int, string, int, ?int, int}>
     */
    public static function getAddressOrOperandSizeDataProvider(): array
    {
        return [
            // No address size prefix
            'Real Mode address size' => [Simulator::REAL_MODE, 'getAddressSize', 0, null, 16],
            'Protected Mode address size' => [Simulator::PROTECTED_MODE, 'getAddressSize', 0, null, 32],
            'Long Mode address size' => [Simulator::LONG_MODE, 'getAddressSize', 0, null, 64],
            'Long Mod (Rex) address size' => [Simulator::LONG_MODE, 'getAddressSize', 0x48, null, 64],

            // Address size prefix applied
            'Real Mode prefixed address size' => [Simulator::REAL_MODE, 'getAddressSize', 0, 0x67, 32],
            'Protected Mode prefixed address size' => [Simulator::PROTECTED_MODE, 'getAddressSize', 0, 0x67, 16],
            'Long Mode prefixed address size' => [Simulator::LONG_MODE, 'getAddressSize', 0, 0x67, 32],
            'Long Mode (Rex) prefixed address size' => [Simulator::LONG_MODE, 'getAddressSize', 0x48, 0x67, 32],

            // No operand size prefix
            'Real Mode operand size' => [Simulator::REAL_MODE, 'getOperandSize', 0, null, 16],
            'Protected Mode operand size' => [Simulator::PROTECTED_MODE, 'getOperandSize', 0, null, 32],
            'Long Mode operand size' => [Simulator::LONG_MODE, 'getOperandSize', 0, null, 32],
            'Long Mod (Rex) operand size' => [Simulator::LONG_MODE, 'getOperandSize', 0x48, null, 64],

            // Operand size prefix applied
            'Real Mode prefixed operand size' => [Simulator::REAL_MODE, 'getOperandSize', 0, 0x66, 32],
            'Protected Mode prefixed operand size' => [Simulator::PROTECTED_MODE, 'getOperandSize', 0, 0x66, 16],
            'Long Mode prefixed operand size' => [Simulator::LONG_MODE, 'getOperandSize', 0, 0x66, 16],
            'Long Mode (Rex) prefixed operand size' => [Simulator::LONG_MODE, 'getOperandSize', 0x48, 0x66, 64],
        ];
    }

    /**
     * @dataProvider getAddressOrOperandSizeDataProvider
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::getAddressSize
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::getOperandSize
     */
    public function testGetAddressOrOperandSize(
        int $simulatorMode,
        string $functionName,
        int $rex,
        ?int $prefix,
        int $expected
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rex);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($check) use ($prefix) {
                      return $check === $prefix;
                  });

        $simulator->method('getLargestInstructionWidth')
                  ->willReturnCallback(function () use ($simulatorMode) {
                      switch ($simulatorMode) {
                          case Simulator::REAL_MODE:
                              return 16;
                          case Simulator::PROTECTED_MODE:
                              return 32;
                          case Simulator::LONG_MODE:
                              return 64;
                      }
                  });

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $func = new ReflectionMethod($instruction, $functionName);
        $func->setAccessible(true);

        $this->assertEquals($expected, $func->invoke($instruction));
    }

    /**
     * @return array<int, array{string, int, int, int}>
     */
    public static function parseModRmByteDataProvider(): array
    {
        return [
            ["\x00", 0, 0, 0], ["\x10", 0, 2, 0], ["\x20", 0, 4, 0], ["\x30", 0, 6, 0],
            ["\x01", 0, 0, 1], ["\x11", 0, 2, 1], ["\x21", 0, 4, 1], ["\x31", 0, 6, 1],
            ["\x02", 0, 0, 2], ["\x12", 0, 2, 2], ["\x22", 0, 4, 2], ["\x32", 0, 6, 2],
            ["\x03", 0, 0, 3], ["\x13", 0, 2, 3], ["\x23", 0, 4, 3], ["\x33", 0, 6, 3],
            ["\x04", 0, 0, 4], ["\x14", 0, 2, 4], ["\x24", 0, 4, 4], ["\x34", 0, 6, 4],
            ["\x05", 0, 0, 5], ["\x15", 0, 2, 5], ["\x25", 0, 4, 5], ["\x35", 0, 6, 5],
            ["\x06", 0, 0, 6], ["\x16", 0, 2, 6], ["\x26", 0, 4, 6], ["\x36", 0, 6, 6],
            ["\x07", 0, 0, 7], ["\x17", 0, 2, 7], ["\x27", 0, 4, 7], ["\x37", 0, 6, 7],
            ["\x08", 0, 1, 0], ["\x18", 0, 3, 0], ["\x28", 0, 5, 0], ["\x38", 0, 7, 0],
            ["\x09", 0, 1, 1], ["\x19", 0, 3, 1], ["\x29", 0, 5, 1], ["\x39", 0, 7, 1],
            ["\x0A", 0, 1, 2], ["\x1A", 0, 3, 2], ["\x2A", 0, 5, 2], ["\x3A", 0, 7, 2],
            ["\x0B", 0, 1, 3], ["\x1B", 0, 3, 3], ["\x2B", 0, 5, 3], ["\x3B", 0, 7, 3],
            ["\x0C", 0, 1, 4], ["\x1C", 0, 3, 4], ["\x2C", 0, 5, 4], ["\x3C", 0, 7, 4],
            ["\x0D", 0, 1, 5], ["\x1D", 0, 3, 5], ["\x2D", 0, 5, 5], ["\x3D", 0, 7, 5],
            ["\x0E", 0, 1, 6], ["\x1E", 0, 3, 6], ["\x2E", 0, 5, 6], ["\x3E", 0, 7, 6],
            ["\x0F", 0, 1, 7], ["\x1F", 0, 3, 7], ["\x2F", 0, 5, 7], ["\x3F", 0, 7, 7],

            ["\x40", 1, 0, 0], ["\x50", 1, 2, 0], ["\x60", 1, 4, 0], ["\x70", 1, 6, 0],
            ["\x41", 1, 0, 1], ["\x51", 1, 2, 1], ["\x61", 1, 4, 1], ["\x71", 1, 6, 1],
            ["\x42", 1, 0, 2], ["\x52", 1, 2, 2], ["\x62", 1, 4, 2], ["\x72", 1, 6, 2],
            ["\x43", 1, 0, 3], ["\x53", 1, 2, 3], ["\x63", 1, 4, 3], ["\x73", 1, 6, 3],
            ["\x44", 1, 0, 4], ["\x54", 1, 2, 4], ["\x64", 1, 4, 4], ["\x74", 1, 6, 4],
            ["\x45", 1, 0, 5], ["\x55", 1, 2, 5], ["\x65", 1, 4, 5], ["\x75", 1, 6, 5],
            ["\x46", 1, 0, 6], ["\x56", 1, 2, 6], ["\x66", 1, 4, 6], ["\x76", 1, 6, 6],
            ["\x47", 1, 0, 7], ["\x57", 1, 2, 7], ["\x67", 1, 4, 7], ["\x77", 1, 6, 7],
            ["\x48", 1, 1, 0], ["\x58", 1, 3, 0], ["\x68", 1, 5, 0], ["\x78", 1, 7, 0],
            ["\x49", 1, 1, 1], ["\x59", 1, 3, 1], ["\x69", 1, 5, 1], ["\x79", 1, 7, 1],
            ["\x4A", 1, 1, 2], ["\x5A", 1, 3, 2], ["\x6A", 1, 5, 2], ["\x7A", 1, 7, 2],
            ["\x4B", 1, 1, 3], ["\x5B", 1, 3, 3], ["\x6B", 1, 5, 3], ["\x7B", 1, 7, 3],
            ["\x4C", 1, 1, 4], ["\x5C", 1, 3, 4], ["\x6C", 1, 5, 4], ["\x7C", 1, 7, 4],
            ["\x4D", 1, 1, 5], ["\x5D", 1, 3, 5], ["\x6D", 1, 5, 5], ["\x7D", 1, 7, 5],
            ["\x4E", 1, 1, 6], ["\x5E", 1, 3, 6], ["\x6E", 1, 5, 6], ["\x7E", 1, 7, 6],
            ["\x4F", 1, 1, 7], ["\x5F", 1, 3, 7], ["\x6F", 1, 5, 7], ["\x7F", 1, 7, 7],

            ["\x80", 2, 0, 0], ["\x90", 2, 2, 0], ["\xA0", 2, 4, 0], ["\xB0", 2, 6, 0],
            ["\x81", 2, 0, 1], ["\x91", 2, 2, 1], ["\xA1", 2, 4, 1], ["\xB1", 2, 6, 1],
            ["\x82", 2, 0, 2], ["\x92", 2, 2, 2], ["\xA2", 2, 4, 2], ["\xB2", 2, 6, 2],
            ["\x83", 2, 0, 3], ["\x93", 2, 2, 3], ["\xA3", 2, 4, 3], ["\xB3", 2, 6, 3],
            ["\x84", 2, 0, 4], ["\x94", 2, 2, 4], ["\xA4", 2, 4, 4], ["\xB4", 2, 6, 4],
            ["\x85", 2, 0, 5], ["\x95", 2, 2, 5], ["\xA5", 2, 4, 5], ["\xB5", 2, 6, 5],
            ["\x86", 2, 0, 6], ["\x96", 2, 2, 6], ["\xA6", 2, 4, 6], ["\xB6", 2, 6, 6],
            ["\x87", 2, 0, 7], ["\x97", 2, 2, 7], ["\xA7", 2, 4, 7], ["\xB7", 2, 6, 7],
            ["\x88", 2, 1, 0], ["\x98", 2, 3, 0], ["\xA8", 2, 5, 0], ["\xB8", 2, 7, 0],
            ["\x89", 2, 1, 1], ["\x99", 2, 3, 1], ["\xA9", 2, 5, 1], ["\xB9", 2, 7, 1],
            ["\x8A", 2, 1, 2], ["\x9A", 2, 3, 2], ["\xAA", 2, 5, 2], ["\xBA", 2, 7, 2],
            ["\x8B", 2, 1, 3], ["\x9B", 2, 3, 3], ["\xAB", 2, 5, 3], ["\xBB", 2, 7, 3],
            ["\x8C", 2, 1, 4], ["\x9C", 2, 3, 4], ["\xAC", 2, 5, 4], ["\xBC", 2, 7, 4],
            ["\x8D", 2, 1, 5], ["\x9D", 2, 3, 5], ["\xAD", 2, 5, 5], ["\xBD", 2, 7, 5],
            ["\x8E", 2, 1, 6], ["\x9E", 2, 3, 6], ["\xAE", 2, 5, 6], ["\xBE", 2, 7, 6],
            ["\x8F", 2, 1, 7], ["\x9F", 2, 3, 7], ["\xAF", 2, 5, 7], ["\xBF", 2, 7, 7],

            ["\xC0", 3, 0, 0], ["\xD0", 3, 2, 0], ["\xE0", 3, 4, 0], ["\xF0", 3, 6, 0],
            ["\xC1", 3, 0, 1], ["\xD1", 3, 2, 1], ["\xE1", 3, 4, 1], ["\xF1", 3, 6, 1],
            ["\xC2", 3, 0, 2], ["\xD2", 3, 2, 2], ["\xE2", 3, 4, 2], ["\xF2", 3, 6, 2],
            ["\xC3", 3, 0, 3], ["\xD3", 3, 2, 3], ["\xE3", 3, 4, 3], ["\xF3", 3, 6, 3],
            ["\xC4", 3, 0, 4], ["\xD4", 3, 2, 4], ["\xE4", 3, 4, 4], ["\xF4", 3, 6, 4],
            ["\xC5", 3, 0, 5], ["\xD5", 3, 2, 5], ["\xE5", 3, 4, 5], ["\xF5", 3, 6, 5],
            ["\xC6", 3, 0, 6], ["\xD6", 3, 2, 6], ["\xE6", 3, 4, 6], ["\xF6", 3, 6, 6],
            ["\xC7", 3, 0, 7], ["\xD7", 3, 2, 7], ["\xE7", 3, 4, 7], ["\xF7", 3, 6, 7],
            ["\xC8", 3, 1, 0], ["\xD8", 3, 3, 0], ["\xE8", 3, 5, 0], ["\xF8", 3, 7, 0],
            ["\xC9", 3, 1, 1], ["\xD9", 3, 3, 1], ["\xE9", 3, 5, 1], ["\xF9", 3, 7, 1],
            ["\xCA", 3, 1, 2], ["\xDA", 3, 3, 2], ["\xEA", 3, 5, 2], ["\xFA", 3, 7, 2],
            ["\xCB", 3, 1, 3], ["\xDB", 3, 3, 3], ["\xEB", 3, 5, 3], ["\xFB", 3, 7, 3],
            ["\xCC", 3, 1, 4], ["\xDC", 3, 3, 4], ["\xEC", 3, 5, 4], ["\xFC", 3, 7, 4],
            ["\xCD", 3, 1, 5], ["\xDD", 3, 3, 5], ["\xED", 3, 5, 5], ["\xFD", 3, 7, 5],
            ["\xCE", 3, 1, 6], ["\xDE", 3, 3, 6], ["\xEE", 3, 5, 6], ["\xFE", 3, 7, 6],
            ["\xCF", 3, 1, 7], ["\xDF", 3, 3, 7], ["\xEF", 3, 5, 7], ["\xFF", 3, 7, 7],
        ];
    }

    /**
     * @dataProvider parseModRmByteDataProvider
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseModRmByte
     */
    public function testParseModRmByte(
        string $byte,
        int $modVal,
        int $regVal,
        int $rmVal
    ): void {
        $expected = [
            'mod' => $modVal,
            'reg' => $regVal,
            'rm' => $rmVal,
        ];

        $instruction = new TestAssemblyInstruction();
        $method = new ReflectionMethod($instruction, "parseModRmByte");
        $method->setAccessible(true);

        $actualByte = $method->invoke($instruction, $byte);

        $this->assertEqualsCanonicalizing($expected, $actualByte);
    }

    /**
     * @return array<string, array{int, int, int, RegisterObj, int, string, int, int, int, int}>
     */
    public static function parseAddressOnMemoryOffsetAddressDataProvider(): array
    {
        return [
            'Long Mode defaults to 64-bit address size' => [
                Simulator::LONG_MODE,
                0x00,
                0x00,
                Register::RBX,
                849,
                "\x10",
                1,
                0,
                3,
                865,
            ],
            'Long Mode (Rex.B) with address size overrides to 32-bit address size' => [
                Simulator::LONG_MODE,
                0x41,
                0x67,
                Register::R11D,
                849,
                "\x10",
                1,
                0,
                3,
                865,
            ],
            'Long Mode (Rex.W) with address size overrides to 32-bit address size' => [
                Simulator::LONG_MODE,
                0x48,
                0x67,
                Register::EBX,
                849,
                "\x10",
                1,
                0,
                3,
                865,
            ],
            'Long Mode with address size overrides to 32-bit address size' => [
                Simulator::LONG_MODE,
                0x00,
                0x67,
                Register::EBX,
                849,
                "\x10",
                1,
                0,
                3,
                865,
            ],
            'Protected Mode defaults to 32-bit address size' => [
                Simulator::PROTECTED_MODE,
                0x00,
                0x00,
                Register::EBX,
                849,
                "\x10\x00\x00\x00",
                2,
                0,
                3,
                865,
            ],
            'Protected Mode with address size overrides to 16-bit address size' => [
                Simulator::PROTECTED_MODE,
                0x00,
                0x67,
                Register::BX,
                849,
                "\x10\x00\x00\x00",
                2,
                0,
                3,
                865,
            ],
            'Real Mode defaults to 16-bit address size' => [
                Simulator::REAL_MODE,
                0x00,
                0x00,
                Register::BX,
                4354,
                "\x10",
                1,
                0,
                3,
                4370,
            ],
            'Real Mode with address size overrides to 32-bit address size' => [
                Simulator::REAL_MODE,
                0x00,
                0x67,
                Register::EBX,
                4354,
                "\x10",
                1,
                0,
                3,
                4370,
            ],

            'Long Mode with REX.B extends base' => [
                Simulator::LONG_MODE,
                0x41,
                0x00,
                Register::R11,
                849,
                "\x10",
                1,
                0,
                3,
                865,
            ],
            'Long Mode with no displacement' => [
                Simulator::LONG_MODE,
                0x00,
                0x00,
                Register::RBX,
                849,
                "",
                0,
                0,
                3,
                849,
            ],
        ];
    }

    /**
     * @dataProvider parseAddressOnMemoryOffsetAddressDataProvider
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseAddress
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseMemoryOffset
     *
     * @param RegisterObj $register
     */
    public function testParseAddressOnMemoryOffsetAddress(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        array $register,
        int $regValue,
        string $addressBytes,
        int $modVal,
        int $regVal,
        int $rmVal,
        int $expect
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('readRegister')
                  ->willReturn($regValue)
                  ->with($register);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $prefixValue === $requested;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $dispSize = 0;

        if (1 === $modVal) {
            $dispSize = 1;
        } elseif (2 === $modVal) {
            $dispSize = 4;
        }

        $simulator->method('getCodeBuffer')
                  ->willReturn($addressBytes)
                  ->with(1, $dispSize);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $method = new ReflectionMethod($instruction, "parseAddress");
        $method->setAccessible(true);

        $byte = [
            'mod' => $modVal,
            'reg' => $regVal,
            'rm' => $rmVal,
        ];
        $address = $method->invoke($instruction, $byte);

        $this->assertInstanceOf(ModRmAddress::class, $address);
        $this->assertEquals($expect, $address->getAddress());
        $this->assertEquals($dispSize, $address->getDisplacement());
    }

    /**
     * @return array<int, array{int, RegisterObj}>
     */
    public static function parseAddressOnModRmDisp32AddressNotInLongModeDataProvider(): array
    {
        return [
            [Simulator::REAL_MODE, Register::BP],
            [Simulator::PROTECTED_MODE, Register::EBP],
        ];
    }

    /**
     * @dataProvider parseAddressOnModRmDisp32AddressNotInLongModeDataProvider
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseAddress
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseMemoryOffset
     *
     * @param RegisterObj $reg
     */
    public function testParseAddressOnModRmDisp32AddressNotInLongMode(
        int $mode,
        array $reg
    ): void {
        // mov edx, 0x10722a80
        // 0x8b 0x15 0x80 0x2a 0x72 0x10
        $simulator = $this->getMockSimulator($mode);

        $simulator->expects($this->never())
                  ->method('readRegister')
                  ->with($reg)
                  ->willReturn(100);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getInstructionPointer')
                  ->willReturn(2);

        $simulator->method('getCodeBuffer')
                  ->willReturn("\x80\x2a\x72\x10")
                  ->with(2, 4);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $method = new ReflectionMethod($instruction, "parseAddress");
        $method->setAccessible(true);

        $byte = [
            'mod' => 0,
            'reg' => 2,
            'rm' => 5,
        ];
        $address = $method->invoke($instruction, $byte);

        $this->assertInstanceOf(ModRmAddress::class, $address);
        $this->assertEquals(0x10722a80, $address->getAddress());
        $this->assertEquals(4, $address->getDisplacement());
    }

    /**
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseAddress
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseRipAddress
     */
    public function testParseAddressAcceptsRipAddressOnLongMode(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturn("\xe0\x17\x19\x0F")
                  ->with(4);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $simulator->method('getAddressBase')
                  ->willReturn(0xF000000000);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");
        $parseAddress->setAccessible(true);

        $byte = [
            "mod" => 0,
            "reg" => 0b111,
            "rm" => 0b101,
        ];

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertInstanceOf(RipAddress::class, $address);
        $this->assertEquals(0xF00F1917E5, $address->getAddress());
    }

    /**
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseAddress
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseMemoryOffset
     */
    public function testParseAddressLooksLikeRipOnProtectedModeReturnsModRmAddress(): void
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeBuffer')
                  ->willReturn("\xe0\x17\x19\x0F")
                  ->with(1, 4);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");
        $parseAddress->setAccessible(true);

        $byte = [
            "mod" => 0,
            "reg" => 0b111,
            "rm" => 0b101,
        ];

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertInstanceOf(ModRmAddress::class, $address);
        $this->assertEquals(0xF1917E0, $address->getAddress());
    }

    /**
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseAddress
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseMemoryOffset
     */
    public function testParseAddressDoesNotUseRipOnModRmMod1(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getCodeBuffer')
                  ->willReturn("\xe0")
                  ->with(1, 1);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");
        $parseAddress->setAccessible(true);

        $byte = [
            "mod" => 1,
            "reg" => 0b111,
            "rm" => 0b101,
        ];

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertNotInstanceOf(RipAddress::class, $address);
    }

    /**
     * @return array<int, array{int, int, RegisterObj, int, RegisterObj, int, int, int, int, string, ?string, int}>
     */
    public static function parseSibAddressDataProvider(): array
    {
        return [
            // mov [eax+ebx*4],ecx
            // 0x89 0x0C 0x98
            [Simulator::PROTECTED_MODE, 0, Register::EBX, 426, Register::EAX, 54923, 0, 1, 4, "\x98", null, 56627],

            // mov [rax+r12*4],ecx
            // 0x89 0x0C 0xA0
            [Simulator::LONG_MODE, 0x4A, Register::R12, 428, Register::RAX, 54925, 0, 1, 4, "\xA0", null, 56637],

            // mov eax,[ebp+eax*4+0x31]
            // 0x8B 0x44 0x85 0x31
            [Simulator::PROTECTED_MODE, 0, Register::EAX, 12, Register::EBP, 498, 1, 0, 4, "\x85", "\x31", 595],

            // mov eax,[ebp+eax*2+0x40201030]
            // 0x8B 0x84 0x45 0x30 0x10 0x20 0x40
            [Simulator::PROTECTED_MODE, 0, Register::EAX, 12, Register::EBP, 498, 2, 0, 4, "\x45", "\x30\x10\x20\x40", 1075843642],

            // mov [rsp+0x10],rsi
            // 0x48 0x89 0x74 0x24 0x10
            [Simulator::LONG_MODE, 0x48, Register::RSP, 0, Register::RSP, 432, 1, 6, 4, "\x24", "\x10", 448],
        ];
    }

    /**
     * @dataProvider parseSibAddressDataProvider
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseAddress
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseSibByte
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseSibAddress
     *
     * @param RegisterObj $indexReg
     * @param RegisterObj $baseReg
     */
    public function testParseSibAddress(
        int $simulatorMode,
        int $rexValue,
        array $indexReg,
        int $indexVal,
        array $baseReg,
        int $baseVal,
        int $modVal,
        int $regVal,
        int $rmVal,
        string $sibByte,
        ?string $dispByte,
        int $expected
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function () {
                      return false;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturn(2);

        // This would indicate the (none) scale.
        if ($indexReg['offset'] === Register::SP['offset']) {
            $indexReg = null;
        }

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register) use (
                      $baseReg,
                      $baseVal,
                      $indexReg,
                      $indexVal
                  ) {
                      switch ($register) {
                          case $baseReg:
                              return $baseVal;
                          case $indexReg:
                              return $indexVal;
                      }

                      $this->fail('Incorrect register ' . $register['name']);
                  });

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturn($sibByte)
                  ->with(strlen($sibByte));

        $dispByteLen = 0;
        if (! is_null($dispByte)) {
            $dispByteLen = strlen($dispByte);
            $simulator->method('getCodeBuffer')
                      ->willReturn($dispByte)
                      ->with(3, $dispByteLen);
        }

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $byte = [
            "mod" => $modVal,
            "reg" => $regVal,
            "rm" => $rmVal,
        ];

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");
        $parseAddress->setAccessible(true);

        $address = $parseAddress->invoke($instruction, $byte);

        $this->assertInstanceOf(SibAddress::class, $address);
        $this->assertEquals($expected, $address->getAddress());
        $this->assertEquals(1 + $dispByteLen, $address->getDisplacement());
    }

    /**
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseAddress
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseSibByte
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::parseSibAddress
     */
    public function testParseAddressWithSibDisp32OverrideInProtectedMode(): void
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getInstructionPointer')
                  ->willReturn(3);

        $simulator->method('getCodeAtInstructionPointer')
                  ->willReturn("\x25")
                  ->with(1);

        $simulator->expects($this->once())
                  ->method('getCodeBuffer')
                  ->willReturn("\x28\x00\x00\x00")
                  ->with(4, 4);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $byte = [
            "mod" => 0,
            "reg" => 0,
            "rm" => 0b100,
        ];

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");
        $parseAddress->setAccessible(true);

        $address = $parseAddress->invoke($instruction, $byte);

        $this->assertInstanceOf(SibAddress::class, $address);
        $this->assertEquals(40, $address->getAddress());
        $this->assertEquals(5, $address->getDisplacement());
    }

    /**
     * @return array<int, array{int, string, int}>
     */
    public static function unpackImmediateDataProvider(): array
    {
        return [
            [8, "\x32", 0x32],
            [8, "\x12", 0x12],
            [16, "\x32\x15", 0x1532],
            [16, "\x93\xFA", 0xFA93],
            [32, "\x90\x21\x43\x42", 0x42432190],
            [32, "\x12\x34\x56\x78", 0x78563412],
            [64, "\x01\x23\x45\xE7\x89\xAB\xCD\x6F", 0x6FCDAB89E7452301],
            [64, "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x0F", 0xFFFFFFFFFFFFFFF],
        ];
    }

    /**
     * @dataProvider unpackImmediateDataProvider
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::unpackImmediate
     */
    public function testUnpackImmediate(
        int $operandSize,
        string $binaryString,
        int $expected
    ): void {
        $instruction = new TestAssemblyInstruction();

        $unpackMethod = new ReflectionMethod($instruction, "unpackImmediate");
        $unpackMethod->setAccessible(true);

        $actual = $unpackMethod->invoke($instruction, $binaryString, $operandSize);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider unpackImmediateDataProvider
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::unpackImmediate
     */
    public function testUnpackImmediateDoesNotOverrideErrorReporting(
        int $operandSize,
        string $binaryString,
        int $expected
    ): void {
        $instruction = new TestAssemblyInstruction();

        $unpackMethod = new ReflectionMethod($instruction, "unpackImmediate");
        $unpackMethod->setAccessible(true);

        $originalErrorLevel = error_reporting();
        try {
            $actual = $unpackMethod->invoke($instruction, $binaryString, $operandSize);
            $this->assertEquals($expected, $actual);
            $this->assertEquals($originalErrorLevel, error_reporting());

            // Now we will test again to check our error level is retained.
            error_reporting(3);
            $unpackMethod->invoke($instruction, $binaryString, $operandSize);
            $this->assertEquals(3, error_reporting());
        } finally {
            error_reporting($originalErrorLevel);
        }
    }

    /**
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::unpackImmediate
     */
    public function testUnpackThrowsExceptionOnInvalidImmediateForSize(): void
    {
        $instruction = new TestAssemblyInstruction();

        $unpackMethod = new ReflectionMethod($instruction, "unpackImmediate");
        $unpackMethod->setAccessible(true);

        $this->expectException(\UnexpectedValueException::class);
        $unpackMethod->invoke($instruction, "\x1", 64);
    }

    /**
     * @dataProvider unpackImmediateDataProvider
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::packImmediate
     */
    public function testPackImmediate(
        int $operandSize,
        string $expected,
        int $immediateToPack
    ): void {
        $instruction = new TestAssemblyInstruction();

        $unpackMethod = new ReflectionMethod($instruction, "packImmediate");
        $unpackMethod->setAccessible(true);

        $actual = $unpackMethod->invoke($instruction, $immediateToPack, $operandSize);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array<int, array{int, RegisterObj}>
     */
    public static function getStackPointerDataProvider(): array
    {
        return [
            [Simulator::REAL_MODE, Register::SP],
            [Simulator::PROTECTED_MODE, Register::ESP],
            [Simulator::LONG_MODE, Register::RSP],
        ];
    }

    /**
     * @dataProvider getStackPointerDataProvider
     * @small
     *
     * @covers shanept\AssemblySimulator\Instruction\AssemblyInstruction::getStackPointerRegister
     *
     * @param RegisterObj $expectedStackPointer
     */
    public function testGetStackPointer(
        int $mode,
        array $expectedStackPointer
    ): void {
        $simulator = $this->getMockSimulator($mode);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $unpackMethod = new ReflectionMethod($instruction, "getStackPointerRegister");
        $unpackMethod->setAccessible(true);

        $actual = $unpackMethod->invoke($instruction);
        $this->assertEquals($expectedStackPointer, $actual);
    }
}
