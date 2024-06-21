<?php

namespace shanept\AssemblySimulatorTests\Unit;

use shanept\AssemblySimulator\Flags;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Exception;
use shanept\AssemblySimulator\Instruction\AssemblyInstruction;
use shanept\AssemblySimulatorTests\Fakes\TestVoidInstruction;
use shanept\AssemblySimulatorTests\Fakes\TestAssemblyInstruction;

/**
 * @covers shanept\AssemblySimulator\Simulator
 * @small
 */
class SimulatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Index:
     * - 0: Mode Friendly Name
     * - 1: Mode
     * - 2: Default Stack Address Base
     * - 3: Default Stack Size
     * - 4: Default Register Count
     * - 5: Default Address Width
     *
     * @return array<string, array{string, int, int, int, int, int}>
     */
    public static function getModeDefaultsDataProvider(): array
    {
        return [
            'Real Mode' => [
                'real',
                Simulator::REAL_MODE,
                0x3FFF,
                0x7F,
                8,
                16,
            ],
            'Protected Mode' => [
                'protected',
                Simulator::PROTECTED_MODE,
                0x3FFFFFFF,
                0x7FFF,
                8,
                32,
            ],
            'Long Mode' => [
                'long',
                Simulator::LONG_MODE,
                0x3FFFFFFFFFFFFFFF,
                0xFFFFFF,
                16,
                64,
            ],
        ];
    }

    /**
     * @small
     */
    public function testDefaultsToRealMode(): void
    {
        $simulator = new Simulator();

        $this->assertEquals(Simulator::REAL_MODE, $simulator->getMode());
    }

    /**
     * @dataProvider getModeDefaultsDataProvider
     * @small
     */
    public function testGetAndSetModes(
        string $modeName,
        int $mode,
        int $unused1,
        int $unused2,
        int $unused3,
        int $unused4,
    ): void {
        $simulator = new Simulator();
        $simulator->setMode($mode);
        $simulator->reset();

        $this->assertEquals($mode, $simulator->getMode());
        $this->assertEquals($modeName, $simulator->getModeName());
    }

    /**
     * @small
     */
    public function testDefaultAddressBaseIsZero(): void
    {
        $simulator = new Simulator();

        $this->assertEquals(0, $simulator->getAddressBase());
    }

    /**
     * @small
     */
    public function testGetAddressBaseAfterSet(): void
    {
        $simulator = new Simulator();

        $simulator->setAddressBase(0xdeadbeef);

        $this->assertEquals(0xdeadbeef, $simulator->getAddressBase());
    }

    /**
     * @dataProvider getModeDefaultsDataProvider
     * @small
     */
    public function testGetLargestInstructionWidth(
        string $modeName,
        int $mode,
        int $unused1,
        int $unused2,
        int $unused3,
        int $expected,
    ): void {
        $simulator = new Simulator($mode);

        $actual = $simulator->getLargestInstructionWidth();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array<int, array{string, ?int, ?int, string}>
     */
    public static function getCodeBufferDataProvider(): array
    {
        return [
            ['FullBuffer', null, null, 'FullBuffer'],
            ['FullBuffer', 4, null, 'Buffer'],
            ['FullBuffer', null, 4, 'Full'],
            ['FullBuffer', 4, 4, 'Buff'],
            ['FullBuffer', -2, null, 'er'],
            ['FullBuffer', null, -2, 'FullBuff'],
            ['FullBuffer', -2, -2, ''],
        ];
    }

    /**
     * @dataProvider getCodeBufferDataProvider
     * @small
     */
    public function testSetAndGetCodeBuffer(
        string $fullBuffer,
        ?int $start,
        ?int $length,
        string $expected,
    ): void {
        $simulator = new Simulator();
        $simulator->setCodeBuffer($fullBuffer);

        $actual = $simulator->getCodeBuffer($start, $length);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array<int, array{string, int}>
     */
    public static function getCodeBufferSizeDataProvider(): array
    {
        return [
            ['string', 6],
            ['longerstring', 12],
            ['evenlongerstring', 16],
            ["String with NUL\0 bits\0 in it!", 29],
            ["NUL-terminated string\0", 22],
        ];
    }

    /**
     * @dataProvider getCodeBufferSizeDataProvider
     * @small
     *
     * @depends testSetAndGetCodeBuffer
     */
    public function testGetCodeBufferSize(string $fullBuffer, int $expected): void
    {
        $simulator = new Simulator();
        $simulator->setCodeBuffer($fullBuffer);

        $this->assertEquals($expected, $simulator->getCodeBufferSize());
    }

    /**
     * @small
     *
     * @depends testSetAndGetCodeBuffer
     */
    public function testGetAndSetAndAdvanceInstructionPointer(): void
    {
        $simulator = new Simulator();
        $simulator->setCodeBuffer('thisisareallyreallyreallyreallyreallylongstringthatis70characterslong!');

        $simulator->setInstructionPointer(10);
        $this->assertEquals(10, $simulator->getInstructionPointer());

        $simulator->setInstructionPointer(5);
        $this->assertEquals(5, $simulator->getInstructionPointer());

        $simulator->advanceInstructionPointer(7);
        $this->assertEquals(12, $simulator->getInstructionPointer());
    }

    /**
     * @small
     *
     * @param MockSimulator $simulator
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function registerNopMock($simulator): void
    {
        $nopMock = $this->createMock(TestAssemblyInstruction::class);

        $nopMock->expects($this->once())
                ->method('mockableCallback')
                ->willReturnCallback(function () use ($simulator) {
                    $simulator->advanceInstructionPointer(1);
                    return true;
                });

        $simulator->registerInstructions($nopMock, [
            0x90 => [$nopMock, 'mockableCallback'],
        ]);
    }

    /**
     * @small
     *
     * @depends testSetAndGetCodeBuffer
     */
    public function testModeChangeWithoutResetThrowsException(): void
    {
        $simulator = new Simulator();

        $simulator->setMode(Simulator::PROTECTED_MODE);

        $simulator->setCodeBuffer("\x90");

        $this->expectException(Exception\Tainted::class);
        $this->expectExceptionMessage(
            'Attempted to operate a tainted environment. Did you forget to reset?',
        );
        $simulator->simulate();
    }

    /**
     * @dataProvider getModeDefaultsDataProvider
     * @small
     */
    public function testRegistersAreClearAfterReset(
        string $modeName,
        int $mode,
        int $unused1,
        int $unused2,
        int $numRegisters,
        int $unused3,
    ): void {
        $simulator = new Simulator($mode);

        // Change the state of the registers
        $simulator->writeRegister(Register::AX, 10);
        $simulator->writeRegister(Register::CX, 20);

        $registers = $simulator->getRawRegisters();
        $this->assertCount($numRegisters, $registers);

        // Confirm the state change
        $different = false;
        for ($i = 0; $i < $numRegisters; $i++) {
            // We don't care about the state of the stack pointer.
            if ($i === Register::SP['offset']) {
                continue;
            }

            if (0 !== $registers[$i]) {
                $different = true;
                break;
            }
        }

        if (! $different) {
            $this->markTestSkipped();
        }

        $simulator->reset();

        $expected = array_fill(0, $numRegisters, 0);
        $registers = $simulator->getRawRegisters();

        unset($expected[Register::SP['offset']]);
        unset($registers[Register::SP['offset']]);

        $this->assertEquals($expected, $registers);
    }

    /**
     * @small
     *
     * @depends testGetStack
     */
    public function testStackIsClearedAfterReset(): void
    {
        $simulator = new Simulator();

        $simulator->writeStackAt(0x3FFF, "\x12");

        $simulator->reset();

        $this->assertEquals("", $simulator->getStack());
    }

    /**
     * @small
     */
    public function testCodeBufferIsClearedAfterReset(): void
    {
        $simulator = new Simulator();

        $simulator->setCodeBuffer("1234");

        $simulator->reset();

        $this->assertEquals("", $simulator->getCodeBuffer());
        $this->assertEquals(0, $simulator->getCodeBufferSize());
    }

    /**
     * @small
     */
    public function testReadRegisterOnSizeLargerThanMachineWidth(): void
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $this->expectException(\RuntimeException::class);
        $simulator->readRegister(Register::RAX);
    }

    /**
     * @small
     */
    public function testSetFlag(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setFlag(Flags::OF, 1);

        $this->assertEquals(1, $simulator->getFlag(Flags::OF));
    }

    /**
     * @depends testSetFlag
     * @small
     */
    public function testMultipleSetFlags(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setFlag(Flags::CF, 1);
        $simulator->setFlag(Flags::AF, 1);

        $this->assertEquals(0b10001, $simulator->getFlags());
        $this->assertTrue($simulator->getFlag(Flags::CF));
        $this->assertTrue($simulator->getFlag(Flags::AF));
    }

    /**
     * @small
     */
    public function testDefaultStackIsEmpty(): void
    {
        $simulator = new Simulator();

        $this->assertEmpty($simulator->getStack());
    }

    /**
     * @small
     */
    public function testWriteStackAddressTaintsSimulator(): void
    {
        $simulator = new Simulator();
        $simulator->setStackAddress(0);

        $this->expectException(Exception\Tainted::class);
        $this->expectExceptionMessage(
            'Attempted to operate a tainted environment. Did you forget to reset?',
        );
        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testWriteStackAddressChangesStackAddress(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $stackAddress = $simulator->readRegister(Register::SP);

        $simulator->setStackAddress(1);
        $simulator->reset();

        $newAddress = $simulator->readRegister(Register::SP);

        $this->assertEquals(1, $newAddress);
        $this->assertNotEquals($stackAddress, $newAddress);
    }

    /**
     * @small
     */
    public function testWriteStackSizeTaintsSimulator(): void
    {
        $simulator = new Simulator();
        $simulator->setStackSize(1);

        $this->expectException(Exception\Tainted::class);
        $this->expectExceptionMessage(
            'Attempted to operate a tainted environment. Did you forget to reset?',
        );
        $simulator->simulate();
    }

    /**
     * @depends testWriteStackAddressTaintsSimulator
     * @depends testWriteStackAddressChangesStackAddress
     * @small
     */
    public function testWriteStackSize(): void
    {
        $simulator = new Simulator();
        $simulator->setStackAddress(99);
        $simulator->reset();

        $simulator->writeStackAt(90, "\0");

        $simulator->setStackSize(5);
        $simulator->reset();

        $this->expectException(\RangeException::class);
        $this->expectExceptionMessage(sprintf(
            'Exceeded maximum stack size. Attempted to allocate %d ' .
            'new bytes to the stack, however it exceeds the maximum ' .
            'stack size of %d.',
            10,
            5,
        ));
        $simulator->writeStackAt(90, "\0");
    }

    /**
     * @dataProvider getModeDefaultsDataProvider
     * @small
     *
     * @depends testWriteStackSize
     */
    public function testDefaultStackAddressForModes(
        string $modeName,
        int $mode,
        int $expectedAddress,
        int $unused1,
        int $unused2,
        int $unused3,
    ): void {
        $simulator = new Simulator($mode);

        $simulator->writeStackAt($expectedAddress, "\x00");

        $this->expectException(Exception\StackUnderflow::class);
        $simulator->writeStackAt($expectedAddress + 1, "\x00");
    }

    /**
     * @dataProvider getModeDefaultsDataProvider
     * @small
     *
     * @depends testWriteStackSize
     * @depends testDefaultStackAddressForModes
     */
    public function testDefaultStackSizeForModes(
        string $modeName,
        int $mode,
        int $address,
        int $expectedSize,
        int $unused1,
        int $unused2,
    ): void {
        $simulator = new Simulator($mode);

        $simulator->writeStackAt($address - $expectedSize + 1, "\x00");

        $this->expectException(\RangeException::class);
        $simulator->writeStackAt($address - $expectedSize, "\x00");
    }

    /**
     * @return array<int, array{int}>
     */
    public static function writeStackPastLimitsThrowsExceptionDataProvider(): array
    {
        return [
            [127],
            [0x3FFF],
            [0x4001],
            [0x5007],
        ];
    }

    /**
     * This unit test sets a stack of X bytes, then writes to X bytes into that
     * stack. This fails because our stack size is 1-based, whereas our stack
     * offset is 0-based. For example, a stack with a size of 1, will start at
     * offset 0. Attempts to write to offset 1 would overflow.
     *
     * @dataProvider writeStackPastLimitsThrowsExceptionDataProvider
     * @small
     *
     * @depends testWriteStackAddressTaintsSimulator
     * @depends testWriteStackAddressChangesStackAddress
     * @depends testWriteStackSize
     */
    public function testWriteStackPastMemoryLimitThrowsException(
        int $stackSize,
    ): void {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setStackSize($stackSize);
        $simulator->setStackAddress($stackSize);

        $simulator->writeStackAt($stackSize - 3, "\x00\x00\x00\x00");

        $this->expectException(\RangeException::class);
        $this->expectExceptionMessage(sprintf(
            'Exceeded maximum stack size. Attempted to allocate %d ' .
            'new bytes to the stack, however it exceeds the maximum ' .
            'stack size of %d.',
            $stackSize - 3,
            $stackSize,
        ));
        $simulator->writeStackAt(0, "fail");
    }

    /**
     * @return array<int, array{array<int, string>, string}>
     */
    public static function writeStackDataProvider(): array
    {
        return [
            [[0 => "\x3", 1 => "\x4"], "\x4\x3"],
            [[0 => "\x3", 2 => "\x4"], "\x4\x0\x3"],
            [[0 => "\x3", 4 => "\x2\x1"], "\x2\x1\x0\x0\x3"],
            [[1 => "\x2"], "\x2\x0"],
            [[126 => "\x34\x45"], "\x34\x45" . str_repeat("\x00", 125)],
        ];
    }

    /**
     * @dataProvider writeStackDataProvider
     * @small
     *
     *
     * @param array<int, string> $values
     */
    public function testGetStack(array $values, string $expected): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $stackAddress = 0x3FFF;
        $simulator->setStackAddress($stackAddress);
        $simulator->setStackSize(127);

        foreach ($values as $position => $value) {
            $simulator->writeStackAt($stackAddress - $position, $value);
        }

        $this->assertEquals($expected, $simulator->getStack());
    }

    /**
     * @dataProvider writeStackDataProvider
     * @small
     *
     * @depends testWriteStackAddressTaintsSimulator
     * @depends testWriteStackAddressChangesStackAddress
     * @depends testWriteStackSize
     *
     * @param array<int, string> $values
     */
    public function testReadStackAt(array $values, string $unused): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $stackAddress = 0x3FFF;
        $simulator->setStackAddress($stackAddress);
        $simulator->setStackSize(127);

        foreach ($values as $position => $value) {
            $simulator->writeStackAt($stackAddress - $position, $value);
        }

        foreach ($values as $position => $expected) {
            $length = strlen($expected);
            $actual = $simulator->readStackAt($stackAddress - $position, $length);

            $message = sprintf(
                'Assertion failure at position %d (0x%X).',
                $position,
                $stackAddress - $position,
            );

            $this->assertEquals($expected, $actual, $message);
        }
    }

    /**
     * @return array<int, array{array<int, string>, string}>
     */
    public static function overwriteStackDataProvider(): array
    {
        return [
            [
                [
                    3 => "\x34\x65\x42\x12",
                    5 => "\x60\x35",
                    4 => "\x00\x00",
                ],
                "\x60\x00\x00\x65\x42\x12",
            ],
            [
                [
                    2 => "\x45\x9A\x4F",
                    1 => "\x00",
                ],
                "\x45\x00\x4F",
            ],
        ];
    }

    /**
     * Now, this one will be more tricky. We will set up the stack then
     * overwrite part of it to see if it gives us the correct value.
     *
     * @dataProvider overwriteStackDataProvider
     *
     * @param array<int, string> $writes
     */
    public function testOverwriteStack(
        array $writes,
        string $expected,
    ): void {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $stackAddress = 0x3FFF;
        $simulator->setStackAddress($stackAddress);

        foreach ($writes as $position => $value) {
            $simulator->writeStackAt($stackAddress - $position, $value);
        }

        $this->assertEquals($expected, $simulator->getStack());
    }

    public function testOverwriteStackWithIdenticalOffset(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $stackAddress = 0x3FFF;
        $simulator->setStackAddress($stackAddress);

        $simulator->writeStackAt(0x3FFE, "\xC3\x01");
        $simulator->writeStackAt(0x3FFE, "\x02");

        $this->assertEquals("\x02\x01", $simulator->getStack());
    }

    /**
     * @return array<int, array{array<int, string>, int, string}>
     */
    public static function clearStackAtDataProvider(): array
    {
        return [
            [
                [
                    0 => "\x43",
                    1 => "\x32",
                    2 => "\x65",
                    4 => "\x43\x42",
                ],
                4,
                "\x65\x32\x43",
            ],
            [
                [
                    0 => "\x43",
                    2 => "\x3\x4",
                    3 => "\x78",
                ],
                1,
                "\x78\x3\x0\x43",
            ],
            [
                [
                    0 => "\x32",
                    1 => "\x21",
                    2 => "\x54",
                    3 => "\x89",
                ],
                1,
                "\x89\x54\x0\x32",
            ],
            [
                [
                    0 => "\x42",
                    1 => "\x31",
                    2 => "\x64",
                    3 => "\x79",
                ],
                0,
                "\x79\x64\x31\x0",
            ],
            [
                [
                    2  => "\x12\x34\x56",
                    4  => "\x43\x21",
                    8  => "\x64\x68\x30\x66",
                    10  => "\x42\x42",
                    14 => "\xCC\xCC\xCC\xCC",
                    19 => "\x12\x00\x33\x00\x55",
                ],
                8,
                "\x12\x00\x33\x00\x55\xCC\xCC\xCC\xCC\x42\x42\x00\x00\x00\x00" .
                    "\x43\x21\x12\x34\x56",
            ],
        ];
    }

    /**
     * @dataProvider clearStackAtDataProvider
     * @small
     *
     * @depends testWriteStackAddressTaintsSimulator
     * @depends testWriteStackAddressChangesStackAddress
     * @depends testWriteStackSize
     *
     * @param array<int, string> $stack
     */
    public function testClearStackAt(
        array $stack,
        int $clearIdx,
        string $expected,
    ): void {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $stackAddress = 0x3FFF;
        $simulator->setStackAddress($stackAddress);
        $simulator->setStackSize(127);

        foreach ($stack as $position => $value) {
            $simulator->writeStackAt($stackAddress - $position, $value);
        }

        $length = strlen($stack[$clearIdx] ?? " ");
        $simulator->clearStackAt($stackAddress - $clearIdx, $length);

        $this->assertEquals($expected, $simulator->getStack());
    }

    /**
     * @small
     *
     * @depends testWriteStackAddressChangesStackAddress
     */
    public function testReadStackUnderflowThrowsException(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setStackAddress(0x10);

        $this->expectException(Exception\StackUnderflow::class);
        $this->expectExceptionMessage(sprintf(
            'Stack underflow. Offset 0x%X requested. Stack starts at 0x%X.',
            0x11,
            0x10,
        ));
        $simulator->readStackAt(0x11, 1);
    }

    /**
     * @small
     *
     * @depends testWriteStackAddressTaintsSimulator
     * @depends testWriteStackAddressChangesStackAddress
     * @depends testWriteStackSize
     */
    public function testReadStackAtInvalidOffsetThrowsException(): void
    {
        $simulator = new Simulator();

        $simulator->setStackAddress(0x3FFF);
        $simulator->setStackSize(127);

        $simulator->writeStackAt(0x3FFA, "abcdef");

        $this->expectException(Exception\StackIndex::class);
        $this->expectExceptionMessage(sprintf(
            'Stack offset 0x%X requested, but it exceeds the top of the ' .
            'stack (0x%X)',
            4560,
            0x3FFA,
        ));
        $simulator->readStackAt(4560, 2);
    }

    /**
     * @small
     *
     * @depends testWriteStackAddressChangesStackAddress
     */
    public function testWriteStackUnderflowThrowsException(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setStackAddress(0x10);

        $this->expectException(Exception\StackUnderflow::class);
        $this->expectExceptionMessage(sprintf(
            'Stack underflow. Offset 0x%X requested. Stack starts at 0x%X.',
            0x11,
            0x10,
        ));
        $simulator->writeStackAt(0x11, " ");
    }

    /**
     * @small
     *
     * @depends testWriteStackAddressChangesStackAddress
     */
    public function testClearStackUnderflowThrowsException(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setStackAddress(0x10);

        $this->expectException(Exception\StackUnderflow::class);
        $this->expectExceptionMessage(sprintf(
            'Stack underflow. Offset 0x%X requested. Stack starts at 0x%X.',
            0x11,
            0x10,
        ));
        $simulator->clearStackAt(0x11, 2);
    }

    /**
     * @small
     *
     * @depends testWriteStackAddressTaintsSimulator
     * @depends testWriteStackAddressChangesStackAddress
     * @depends testWriteStackSize
     * @depends testGetStack
     */
    public function testClearStackAtInvalidOffsetThrowsException(): void
    {
        $simulator = new Simulator();

        $simulator->setStackAddress(0x3FFF);
        $simulator->setStackSize(127);

        $simulator->writeStackAt(0x3FFF, "\x01");

        $this->expectException(Exception\StackIndex::class);
        $this->expectExceptionMessage(sprintf(
            'Stack offset 0x%X requested, but it exceeds the top of the ' .
            'stack (0x%X)',
            4560,
            0x3FFE,
        ));
        $simulator->clearStackAt(4560, 1);
    }

    /**
     * @small
     */
    public function testDefaultInstructionPointerIsZero(): void
    {
        $simulator = new Simulator();

        $this->assertEquals(0, $simulator->getInstructionPointer());
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testRegisteredInstructionIsCalled(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstruction = $this->createMock(AssemblyInstruction::class);
        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue(true);
            return true;
        };

        $simulator->registerInstructions($mockInstruction, [
            0x01 => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x01");
        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testSimulatorThrowsExceptionIfOpcodeIsNotRegistered(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $simulator->setAddressBase(0xF);

        $mockInstructionOld = $this->createMock(AssemblyInstruction::class);
        $mockFunctionOld = function () {
            return true;
        };

        $simulator->registerInstructions($mockInstructionOld, [
            0x02 => $mockFunctionOld,
        ]);

        $simulator->setCodeBuffer("\x01");

        $this->expectException(Exception\InvalidOpcode::class);
        $this->expectExceptionMessage('Encountered unknown opcode 0x01 at offset 0 (0xF).');
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testSimulatorThrowsExceptionIfTwoByteOpcodeIsNotRegistered(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);
        $simulator->setAddressBase(0xF);

        $mockInstructionOld = $this->createMock(AssemblyInstruction::class);
        $mockFunctionOld = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            return true;
        };

        $simulator->registerInstructions($mockInstructionOld, [
            0x0F02 => $mockFunctionOld,
        ]);

        $simulator->setCodeBuffer("\x0F\x02\x0F\x04\x01");

        $this->expectException(Exception\InvalidOpcode::class);
        $this->expectExceptionMessage('Encountered unknown opcode 0x0F04 at offset 2 (0x11).');
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testTwoByteInstructionIsntOfferedToOneByteInstruction(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $twoByte = $this->createMock(TestAssemblyInstruction::class);

        // This instruction will work for operand 0F90. We want this one to be called.
        $twoByte->expects($this->once())
                ->method('mockableCallback')
                ->willReturnCallback(function () use ($simulator) {
                    $simulator->advanceInstructionPointer(1);
                    return true;
                });

        // This instruction will work for operand 90. We don't want this instruction called.
        $oneByte = $this->createMock(TestAssemblyInstruction::class);

        $oneByte->expects($this->never())
                ->method('mockableCallback')
                ->willReturnCallback(function () use ($simulator) {
                    $simulator->advanceInstructionPointer(1);
                    return true;
                });

        /**
         * The twoByte handler is intentionally registered first. This means
         * when the oneByte handler is registered, it will have higher priority.
         * If the simulator is not matching two-byte instructions correctly,
         * it should hit the higher priority function first, failing the test.
         */
        $simulator->registerInstructions($twoByte, [
            0x0F90 => [$twoByte, 'mockableCallback'],
        ]);

        $simulator->registerInstructions($oneByte, [
            0x90 => [$oneByte, 'mockableCallback'],
        ]);

        $simulator->setCodeBuffer("\x0F\x90");

        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testSimulatorThrowsExceptionIfAllRegisteredFunctionsReturnFalse(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstructionOld = $this->createMock(AssemblyInstruction::class);
        $mockFunctionOld = function () {
            return false;
        };

        $simulator->registerInstructions($mockInstructionOld, [
            0x01 => $mockFunctionOld,
        ]);

        $simulator->setCodeBuffer("\x01");

        $this->expectException(Exception\InvalidOpcode::class);
        $this->expectExceptionMessage('Encountered unknown opcode 0x01 at offset 0 (0x0).');
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testSimulatorSkipsNonMatchingOpcodeRegistrations(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstructionOld = $this->createMock(AssemblyInstruction::class);
        $mockFunctionOld = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue(true);
            return true;
        };

        $simulator->registerInstructions($mockInstructionOld, [
            0x01 => $mockFunctionOld,
        ]);

        $mockInstructionNew = $this->createMock(TestAssemblyInstruction::class);

        $mockInstructionNew->expects($this->never())
                           ->method('mockableCallback')
                           ->willReturn(false);

        $simulator->registerInstructions($mockInstructionNew, [
            0x03 => [$mockInstructionNew, 'mockableCallback'],
        ]);

        $simulator->setCodeBuffer("\x01");
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testSimulatorThrowsExceptionIfRegisteredClosureDoesntReturnBool(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstruction = $this->createMock(AssemblyInstruction::class);
        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
        };

        $simulator->registerInstructions($mockInstruction, [
            0x01 => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x01");

        $this->expectException(\LogicException::class);
        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testSimulatorThrowsExceptionIfRegisteredInstantiatedClassDoesntReturnBool(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstruction = $this->getMockBuilder(TestVoidInstruction::class)
                                ->onlyMethods(['setSimulator'])
                                ->getMock();

        $simulator->registerInstructions($mockInstruction, [
            0x01 => [$mockInstruction, 'returnVoid'],
        ]);

        $simulator->setCodeBuffer("\x01");

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected boolean return value from %s::returnVoid, but received "NULL" instead.',
            get_class($mockInstruction),
        ));
        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testSimulatorThrowsExceptionIfRegisteredClassStringDoesntReturnBool(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstruction = $this->createMock(TestVoidInstruction::class);

        $simulator->registerInstructions($mockInstruction, [
            0x01 => TestVoidInstruction::class . '::returnVoidStatic',
        ]);

        $simulator->setCodeBuffer("\x01");

        $this->expectException(\LogicException::class);
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testNewerRegisteredFunctionIsGivenPriority(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstructionOld = $this->createMock(TestAssemblyInstruction::class);

        $mockInstructionOld->expects($this->never())
                           ->method('mockableCallback')
                           ->willReturn(false);

        $simulator->registerInstructions($mockInstructionOld, [
            0x01 => [$mockInstructionOld, 'mockableCallback'],
        ]);

        $mockInstructionNew = $this->createMock(AssemblyInstruction::class);
        $mockFunctionNew = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue(true);
            return true;
        };

        $simulator->registerInstructions($mockInstructionNew, [
            0x01 => $mockFunctionNew,
        ]);

        $simulator->setCodeBuffer("\x01");
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testNewerRegisteredFunctionCanDelegateToOldFunction(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstructionOld = $this->createMock(AssemblyInstruction::class);
        $mockFunctionOld = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue(true);
            return true;
        };

        $simulator->registerInstructions($mockInstructionOld, [
            0x01 => $mockFunctionOld,
        ]);

        $mockInstructionNew = $this->createMock(TestAssemblyInstruction::class);

        $mockInstructionNew->expects($this->once())
                           ->method('mockableCallback')
                           ->willReturn(false);

        $simulator->registerInstructions($mockInstructionNew, [
            0x01 => [$mockInstructionNew, 'mockableCallback'],
        ]);

        $simulator->setCodeBuffer("\x01");
        $simulator->simulate();
    }

    /**
     * @return array<int, array{int}>
     */
    public static function rexOnLongModeIsRecordedDataProvider(): array
    {
        return [
            [0x40], [0x41], [0x42], [0x43], [0x44], [0x45], [0x46], [0x47],
            [0x48], [0x49], [0x4A], [0x4B], [0x4C], [0x4D], [0x4E], [0x4F],
        ];
    }

    /**
     * @dataProvider rexOnLongModeIsRecordedDataProvider
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testRexOnLongModeIsRecorded(int $rexValue): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstruction = $this->createMock(AssemblyInstruction::class);
        $mockFunction = function () use ($simulator, $rexValue) {
            $this->assertEquals(1, $simulator->getInstructionPointer());

            $simulator->advanceInstructionPointer(1);

            $this->assertEquals($rexValue, $simulator->getRex());
            $this->assertEquals([$rexValue], $simulator->getPrefixes());
            return true;
        };

        $simulator->registerInstructions($mockInstruction, [
            0x01 => $mockFunction,
        ]);

        $simulator->setCodeBuffer(chr($rexValue) . "\x01");
        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testRexOnProtectedModeThrowsException(): void
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $simulator->setCodeBuffer("\x40\x90");

        $this->expectException(\RuntimeException::class);
        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testRexOnRealModeThrowsException(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setCodeBuffer("\x40\x90");

        $this->expectException(\RuntimeException::class);
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testOp66IsRecordedAsPrefixInLongMode(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue($simulator->hasPrefix(0x66));
            return true;
        };

        $instruction = $this->createMock(AssemblyInstruction::class);
        $simulator->registerInstructions($instruction, [
            0x01 => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x66\x01");
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testOp66IsRecordedAsPrefixInProtectedMode(): void
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue($simulator->hasPrefix(0x66));
            return true;
        };

        $instruction = $this->createMock(AssemblyInstruction::class);
        $simulator->registerInstructions($instruction, [
            0x01 => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x66\x01");
        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testOp66IsIgnoredAsPrefixInRealMode(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setCodeBuffer("\x66");

        $this->expectException(\OutOfBoundsException::class);
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testOp67IsRecordedAsPrefixInLongMode(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue($simulator->hasPrefix(0x67));
            return true;
        };

        $instruction = $this->createMock(AssemblyInstruction::class);
        $simulator->registerInstructions($instruction, [
            0x01 => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x67\x01");
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testOp67IsRecordedAsPrefixInProtectedMode(): void
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue($simulator->hasPrefix(0x67));
            return true;
        };

        $instruction = $this->createMock(AssemblyInstruction::class);
        $simulator->registerInstructions($instruction, [
            0x01 => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x67\x01");
        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testOp67IsIgnoredAsPrefixInRealMode(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setCodeBuffer("\x67");

        $this->expectException(Exception\InvalidOpcode::class);
        $this->expectExceptionMessage('Encountered unknown opcode 0x67 at offset 0 (0x0).');
        $simulator->simulate();
    }

    /**
     * @small
     */
    public function testTwoByteInstructionIsNotCalledInRealMode(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $mockInstruction = $this->createMock(TestAssemblyInstruction::class);
        $mockFunction = function () {
            $this->fail('We should not have reached a 2-byte instruction!');
        };

        $simulator->registerInstructions($mockInstruction, [
            0x0F01  => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x0F\x01");

        $this->expectException(Exception\InvalidOpcode::class);
        $this->expectExceptionMessage('Encountered unknown opcode 0x0F at offset 0 (0x0).');
        $simulator->simulate();
    }

    /**
     * @return array<int, array{int}>
     */
    public static function protectedAndLongModesDataProvider(): array
    {
        return [[Simulator::PROTECTED_MODE], [Simulator::LONG_MODE]];
    }

    /**
     * @dataProvider protectedAndLongModesDataProvider
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testTwoByteInstructionIsCalledInProtectedAndLongModes(int $mode): void
    {
        $simulator = new Simulator($mode);

        $mockInstruction = $this->createMock(TestAssemblyInstruction::class);
        $mockInstruction->expects($this->once())
                        ->method('mockableCallback')
                        ->willReturnCallback(function () use ($simulator) {
                            $simulator->advanceInstructionPointer(1);
                            return true;
                        });

        $simulator->registerInstructions($mockInstruction, [
            0x0F01  => [$mockInstruction, 'mockableCallback'],
        ]);

        $simulator->setCodeBuffer("\x0F\x01");
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     * @depends testOp66IsRecordedAsPrefixInLongMode
     * @depends testOp67IsRecordedAsPrefixInLongMode
     * @depends testTwoByteInstructionIsCalledInProtectedAndLongModes
     * @depends testRexOnLongModeIsRecorded
     */
    public function testGetPrefixReturnsListOfPrefixes(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstruction = $this->createMock(AssemblyInstruction::class);
        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertEquals([0x66, 0x40, 0x0F], $simulator->getPrefixes());
            return true;
        };

        $simulator->registerInstructions($mockInstruction, [
            0x0F09 => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x66\x40\x0F\x09");
        $simulator->simulate();
    }

    /**
     * @small
     *
     * @depends testGetAndSetAndAdvanceInstructionPointer
     */
    public function testRexPrefixTreatedAsInstructionAfterTwoBytePrefix(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstruction = $this->createMock(AssemblyInstruction::class);
        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(2);
            $this->assertTrue(true);
            return true;
        };

        $simulator->registerInstructions($mockInstruction, [
            0x0F40 => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x66\x0F\x40\x11");
        $simulator->simulate();
    }
}
