<?php

namespace shanept\AssemblySimulatorTests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use shanept\AssemblySimulator\Flags;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Exception;
use shanept\AssemblySimulator\Stack\Stack;
use shanept\AssemblySimulator\Instruction\AssemblyInstruction;
use shanept\AssemblySimulatorTests\Fakes\TestVoidInstruction;
use shanept\AssemblySimulatorTests\Fakes\TestAssemblyInstruction;

/**
 * @covers shanept\AssemblySimulator\Simulator
 * @small
 */
class SimulatorTest extends TestCase
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testGetAndSetModes(
        string $modeName,
        int $mode,
        int $unused1,
        int $unused2,
        int $unused3,
        int $unused4
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testGetLargestInstructionWidth(
        string $unused1,
        int $mode,
        int $unused2,
        int $unused3,
        int $unused4,
        int $expected
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
        string $expected
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testRegistersAreClearAfterReset(
        string $unused1,
        int $mode,
        int $unused2,
        int $unused3,
        int $numRegisters,
        int $unused4
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
     */
    public function testStackIsClearedAfterReset(): void
    {
        $mock = $this->createMock(Stack::class);

        $mock->expects($this->exactly(2))
             ->method('clear');

        $simulator = new Simulator(null, ['stack' => $mock]);
        $simulator->reset();
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
     * @return array<string, array{int}>
     */
    public static function allFlagsDataProvider(): array
    {
        return [
            'Carry flag' => [Flags::CARRY],
            'Parity flag' => [Flags::PARITY],
            'Adjust flag' => [Flags::ADJUST],
            'Zero flag' => [Flags::ZERO],
            'Sign flag' => [Flags::SIGN],
            'Trap flag' => [Flags::TRAP],
            'Interruption flag' => [Flags::INTERRUPTION],
            'Direction flag' => [Flags::DIRECTION],
            'Overflow flag' => [Flags::OVERFLOW],
            'Iopl flag' => [Flags::IOPL],
            'Nested flag' => [Flags::NESTED],
            'Resume flag' => [Flags::RESUME],
            'Virtual flag' => [Flags::VIRTUAL],
            'Alignment flag' => [Flags::ALIGNMENT],
            'Vif flag' => [Flags::VIF],
            'Vip flag' => [Flags::VIP],
        ];
    }

    /**
     * @dataProvider allFlagsDataProvider
     * @small
     */
    public function testSetFlag(int $flag): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setFlag($flag, true);
        $this->assertTrue($simulator->getFlag($flag));

        $simulator->setFlag($flag, false);
        $this->assertFalse($simulator->getFlag($flag));
    }

    /**
     * @depends testSetFlag
     * @small
     */
    public function testMultipleSetFlags(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setFlag(Flags::CF, true);
        $simulator->setFlag(Flags::AF, true);

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
    public function testChangeStackAddressChangesStackPointer(): void
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
    public function testWriteToStackChangesStackPointer(): void
    {
        $mock = $this->createMock(Stack::class);
        $simulator = new Simulator(Simulator::REAL_MODE, [
            'stack' => $mock,
        ]);

        $simulator->setStackAddress(10);
        $simulator->reset();

        $simulator->writeStackAt(8, "ab");

        $pointer = $simulator->readRegister(Register::SP);

        $this->assertEquals(8, $pointer);
    }

    /**
     * @small
     */
    public function testClearStackAtChangesStackPointer(): void
    {
        $mock = $this->createMock(Stack::class);

        // This is the length AFTER the clear operation has been performed.
        $mock->method('getLength')
             ->willReturn(1);

        $simulator = new Simulator(Simulator::REAL_MODE, [
            'stack' => $mock,
        ]);

        $simulator->setStackAddress(10);
        $simulator->reset();

        $simulator->writeStackAt(8, "ab");
        $simulator->clearStackAt(8, 1);

        $pointer = $simulator->readRegister(Register::SP);

        $this->assertEquals(9, $pointer);
    }

    /**
     * @return array<string, array{string, string, array<int, int|string>}>
     */
    public static function stackMethodsProxyToDataProvider(): array
    {
        return [
            'Stack read proxy' => ['readStackAt', 'getOffset', [3, 2]],
            'Stack write proxy' => ['writeStackAt', 'setOffset', [2, "s"]],
            'Stack clear proxy' => ['clearStackAt', 'clearOffset', [8, 4]],
        ];
    }

    /**
     * @dataProvider stackMethodsProxyToDataProvider
     * @small
     *
     * @param array<int, int|string> $parameters
     */
    public function testStackMethodsProxyToDataProvider(
        string $simulatorMethodName,
        string $stackMethodName,
        array $parameters
    ): void {
        $mock = $this->createMock(Stack::class);

        $mock->expects($this->exactly(1))
             ->method($stackMethodName)
             ->with(...$parameters);

        $simulator = new Simulator(null, ['stack' => $mock]);
        $callback = [$simulator, $simulatorMethodName];

        if (! is_callable($callback)) {
            $this->fail('Invalid callback ' . $simulatorMethodName);
        }

        call_user_func_array($callback, $parameters);
    }

    /**
     * @small
     */
    public function testSetStackAddressAfterConstruct(): void
    {
        $mock = $this->createMock(Stack::class);

        $called = 0;
        $mock->expects($this->exactly(2))
             ->method('setAddress')
             ->willReturnCallback(function ($address) use (&$called) {
                 $called += 1;

                 if (1 === $called) {
                     return;
                 } elseif (2 === $called) {
                     if (10 === $address) {
                         return;
                     }

                     $message = "setAddress#2 expected a value of 10, got $address.";
                 } else {
                     $message = 'setAddress called too many times';
                 }

                 throw new LogicException($message);
             });

        $simulator = new Simulator(null, ['stack' => $mock]);

        $simulator->setStackAddress(10);
    }

    /**
     * @small
     */
    public function testSetStackSizeAfterConstruct(): void
    {
        $mock = $this->createMock(Stack::class);

        $called = 0;
        $mock->expects($this->exactly(2))
             ->method('limitSize')
             ->willReturnCallback(function ($limit) use (&$called) {
                 $called += 1;

                 if (1 === $called) {
                     return;
                 } elseif (2 === $called) {
                     if (10 === $limit) {
                         return;
                     }

                     $message = "limitSize#2 expected a value of 10, got $limit.";
                 } else {
                     $message = 'limitSize called too many times';
                 }

                 throw new LogicException($message);
             });

        $simulator = new Simulator(null, ['stack' => $mock]);

        $simulator->setStackSize(10);
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
     * @dataProvider getModeDefaultsDataProvider
     * @small
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testDefaultStackAddressForModes(
        string $unused1,
        int $mode,
        int $expectedAddress,
        int $unused2,
        int $unused3,
        int $unused4
    ): void {
        $mock = $this->createMock(Stack::class);

        $mock->expects($this->once())
             ->method('setAddress')
             ->with($expectedAddress);

        new Simulator($mode, ['stack' => $mock]);
    }

    /**
     * @dataProvider getModeDefaultsDataProvider
     * @small
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testDefaultStackSizeForModes(
        string $unused1,
        int $mode,
        int $unused2,
        int $expectedSize,
        int $unused3,
        int $unused4
    ): void {
        $mock = $this->createMock(Stack::class);

        $mock->expects($this->once())
             ->method('limitSize')
             ->with($expectedSize);

        new Simulator($mode, ['stack' => $mock]);
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
    public function testNewerRegisteredFunctionCanDelegateToOlderFunction(): void
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
