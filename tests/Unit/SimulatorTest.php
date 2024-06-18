<?php

namespace shanept\AssemblySimulatorTests\Unit;

use shanept\AssemblySimulator\Flags;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Exception;
use shanept\AssemblySimulator\Instruction\AssemblyInstruction;
use shanept\AssemblySimulatorTests\Fakes\TestVoidInstruction;
use shanept\AssemblySimulatorTests\Fakes\TestAssemblyInstruction;

class SimulatorTest extends \PHPUnit\Framework\TestCase
{
    public function registerNopMock($simulator)
    {
        $nopMock = $this->getMockBuilder(TestAssemblyInstruction::class)
                        ->addMethods(['executeOperand90'])
                        ->getMock();

        $nopMock->expects($this->once())
                ->method('executeOperand90')
                ->willReturnCallback(function () use ($simulator) {
                    $simulator->advanceInstructionPointer(1);
                    return true;
                });

        $simulator->registerInstructions($nopMock, [
            0x90 => [$nopMock, 'executeOperand90'],
        ]);
    }

    public function testDefaultsToRealMode()
    {
        $simulator = new Simulator();

        $this->assertEquals(Simulator::REAL_MODE, $simulator->getMode());
    }

    public function testDefaultAddressBaseIsZero()
    {
        $simulator = new Simulator();

        $this->assertEquals(0, $simulator->getAddressBase());
    }

    public function testGetAddressBaseAfterSet()
    {
        $simulator = new Simulator();

        $simulator->setAddressBase(0xdeadbeef);

        $this->assertEquals(0xdeadbeef, $simulator->getAddressBase());
    }

    public function testGetLargestInstructionWidthOnRealMode()
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $this->assertEquals(16, $simulator->getLargestInstructionWidth());
    }

    public function testGetLargestInstructionWidthOnProtectedMode()
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $this->assertEquals(32, $simulator->getLargestInstructionWidth());
    }

    public function testGetLargestInstructionWidthOnLongMode()
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $this->assertEquals(64, $simulator->getLargestInstructionWidth());
    }

    public function testModeChangeWithoutResetThrowsException()
    {
        $simulator = new Simulator();

        $simulator->setMode(Simulator::PROTECTED_MODE);

        $simulator->setCodeBuffer("\x90");

        $this->expectException(Exception\Tainted::class);
        $simulator->simulate();
    }

    public function testReadRegisterOnSizeLargerThanMachineWidth()
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $this->expectException(\RuntimeException::class);
        $simulator->readRegister(Register::RAX);
    }

    public function testSetFlag()
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setFlag(Flags::OF, 1);

        $this->assertEquals(1, $simulator->getFlag(Flags::OF));
    }

    /**
     * @depends testSetFlag
     */
    public function testMultipleSetFlags()
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setFlag(Flags::CF, 1);
        $simulator->setFlag(Flags::AF, 1);

        $this->assertEquals(0b10001, $simulator->getFlags());
    }

    public function testDefaultStackIsEmpty()
    {
        $simulator = new Simulator();

        $this->assertEmpty($simulator->getStack());
    }

    public function testSetStackAddressTaintsSimulator()
    {
        $simulator = new Simulator();
        $simulator->setStackAddress(0);

        $this->expectException(Exception\Tainted::class);
        $simulator->simulate();
    }

    public function testSetStackAddressChangesStackAddress()
    {
        $simulator = new Simulator();

        $stackAddress = $simulator->readRegister(Register::SP);

        $simulator->setStackAddress(1);
        $simulator->reset();

        $newAddress = $simulator->readRegister(Register::SP);

        $this->assertEquals(1, $newAddress);
        $this->assertNotEquals($stackAddress, $newAddress);
    }

    public function testSetStackSizeTaintsSimulator()
    {
        $simulator = new Simulator();
        $simulator->setStackSize(1);

        $this->expectException(Exception\Tainted::class);
        $simulator->simulate();
    }

    /**
     * @depends testSetStackAddressTaintsSimulator
     * @depends testSetStackAddressChangesStackAddress
     */
    public function testSetStackSize()
    {
        $simulator = new Simulator();
        $simulator->setStackAddress(100);
        $simulator->reset();

        $simulator->writeStackAt(90, "\0");

        $simulator->setStackSize(5);
        $simulator->reset();

        $this->expectException(\RangeException::class);
        $simulator->writeStackAt(90, "\0");
    }

    public function testWriteStackPastMemoryLimitThrowsException()
    {
        $simulator = new Simulator();

        $this->expectException(\RangeException::class);
        $simulator->writeStackAt(0, "fail");
    }

    public static function setStackDataProvider()
    {
        return [
            [ [ 0 => "\x3", 1 => "\x4" ], "\x4\x3" ],
            [ [ 0 => "\x3", 2 => "\x4" ], "\x4\x0\x3" ],
            [ [ 0 => "\x3", 4 => "\x2\x1" ], "\x2\x1\x0\x0\x3" ],
            [ [ 1 => "\x2" ], "\x2\x0" ],
        ];
    }

    /**
     * @dataProvider setStackDataProvider
     */
    public function testGetStack($values, $expected)
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $stackAddress = $simulator->readRegister(Register::SP);

        foreach ($values as $position => $value) {
            $simulator->writeStackAt($stackAddress - $position, $value);
        }

        $this->assertEquals($expected, $simulator->getStack());
    }

    /**
     * @dataProvider setStackDataProvider
     */
    public function testReadStackAt($values, $expected)
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $stackAddress = $simulator->readRegister(Register::SP);

        foreach ($values as $position => $value) {
            $simulator->writeStackAt($stackAddress - $position, $value);
        }

        foreach ($values as $position => $expected) {
            $length = strlen($expected);
            $actual = $simulator->readStackAt($stackAddress - $position, $length);
            $this->assertEquals($expected, $actual);
        }
    }

    public static function clearStackAtDataProvider()
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
        ];
    }

    /**
     * @dataProvider clearStackAtDataProvider
     */
    public function testClearStackAt($stack, $clearIdx, $expected)
    {
        $simulator = new Simulator();

        $startAddress = $simulator->readRegister(Register::SP);

        foreach ($stack as $position => $value) {
            $simulator->writeStackAt($startAddress - $position, $value);
        }

        $length = strlen($stack[$clearIdx] ?? " ");
        $simulator->clearStackAt($startAddress - $clearIdx, $length);

        $this->assertEquals($expected, $simulator->getStack());
    }

    public function testReadStackUnderflowThrowsException()
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $this->expectException(Exception\StackUnderflow::class);
        $simulator->readStackAt(PHP_INT_MAX, 1);
    }

    public function testReadStackAtInvalidOffsetThrowsException()
    {
        $simulator = new Simulator();

        $this->expectException(Exception\StackIndex::class);
        $simulator->readStackAt(4560, 2);
    }

    public function testWriteStackUnderflowThrowsException()
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $this->expectException(Exception\StackUnderflow::class);
        $simulator->writeStackAt(PHP_INT_MAX, " ");
    }

    public function testClearStackUnderflowThrowsException()
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $this->expectException(Exception\StackUnderflow::class);
        $simulator->clearStackAt(PHP_INT_MAX, 2);
    }

    public function testClearStackAtInvalidOffsetThrowsException()
    {
        $simulator = new Simulator();

        $this->expectException(Exception\StackIndex::class);
        $simulator->clearStackAt(4560, 1);
    }

    public static function getCodeBufferDataProvider()
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
     */
    public function testGetCodeBuffer($fullBuffer, $start, $length, $expected)
    {
        $simulator = new Simulator();
        $simulator->setCodeBuffer($fullBuffer);

        $actual = $simulator->getCodeBuffer($start, $length);
        $this->assertEquals($expected, $actual);
    }

    public static function getCodeBufferSizeDataProvider()
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
     */
    public function testGetCodeBufferSize($fullBuffer, $expected)
    {
        $simulator = new Simulator();
        $simulator->setCodeBuffer($fullBuffer);

        $this->assertEquals($expected, $simulator->getCodeBufferSize());
    }

    public function testDefaultInstructionPointerIsZero()
    {
        $simulator = new Simulator();

        $this->assertEquals(0, $simulator->getInstructionPointer());
    }

    public function testGetAndSetAndAdvanceInstructionPointer()
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

    public function testRegisteredInstructionIsCalled()
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

    public function testSimulatorThrowsExceptionIfOpcodeIsNotRegistered()
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstructionOld = $this->createMock(AssemblyInstruction::class);
        $mockFunctionOld = function () {
            return true;
        };

        $simulator->registerInstructions($mockInstructionOld, [
            0x02 => $mockFunctionOld,
        ]);

        $simulator->setCodeBuffer("\x01");

        $this->expectException(Exception\InvalidOpcode::class);
        $simulator->simulate();
    }

    public function testSimulatorThrowsExceptionIfTwoByteOpcodeIsNotRegistered()
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstructionOld = $this->createMock(AssemblyInstruction::class);
        $mockFunctionOld = function () {
            return true;
        };

        $simulator->registerInstructions($mockInstructionOld, [
            0x0F02 => $mockFunctionOld,
        ]);

        $simulator->setCodeBuffer("\x0F\x01");

        $this->expectException(\OutOfBoundsException::class);
        $simulator->simulate();
    }

    public function testTwoByteInstructionIsntOfferedToOneByteInstruction()
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $twoByte = $this->getMockBuilder(TestAssemblyInstruction::class)
                        ->addMethods(['executeOperand0F90'])
                        ->getMock();

        $twoByte->expects($this->once())
                ->method('executeOperand0F90')
                ->willReturnCallback(function () use ($simulator) {
                    $simulator->advanceInstructionPointer(1);
                    return true;
                });

        $oneByte = $this->getMockBuilder(TestAssemblyInstruction::class)
                        ->addMethods(['executeOperand90'])
                        ->getMock();

        $oneByte->expects($this->never())
                ->method('executeOperand90')
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
            0x0F90 => [$twoByte, 'executeOperand0F90'],
        ]);

        $simulator->registerInstructions($oneByte, [
            0x90 => [$oneByte, 'executeOperand90'],
        ]);

        $simulator->setCodeBuffer("\x0F\x90");

        $simulator->simulate();
    }

    public function testSimulatorThrowsExceptionIfAllRegisteredFunctionsReturnFalse()
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

        $this->expectException(\OutOfBoundsException::class);
        $simulator->simulate();
    }

    public function testSimulatorSkipsNonMatchingOpcodeRegistrations()
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

        $mockInstructionNew = $this->getMockBuilder(TestAssemblyInstruction::class)
                                   ->addMethods(['executeOperand3'])
                                   ->getMock();

        $mockInstructionNew->expects($this->never())
                           ->method('executeOperand3')
                           ->willReturn(false);

        $simulator->registerInstructions($mockInstructionNew, [
            0x03 => [$mockInstructionNew, 'executeOperand3'],
        ]);

        $simulator->setCodeBuffer("\x01");
        $simulator->simulate();
    }

    public function testSimulatorThrowsExceptionIfRegisteredClosureDoesntReturnBool()
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

    public function testSimulatorThrowsExceptionIfRegisteredInstantiatedClassDoesntReturnBool()
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
        $simulator->simulate();
    }

    public function testSimulatorThrowsExceptionIfRegisteredClassStringDoesntReturnBool()
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

    public function testNewerRegisteredFunctionIsGivenPriority()
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstructionOld = $this->getMockBuilder(TestAssemblyInstruction::class)
                                   ->addMethods(['executeOperand1'])
                                   ->getMock();

        $mockInstructionOld->expects($this->never())
                           ->method('executeOperand1')
                           ->willReturn(false);

        $simulator->registerInstructions($mockInstructionOld, [
            0x01 => [$mockInstructionOld, 'executeOperand1'],
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

    public function testNewerRegisteredFunctionCanDelegateToOldFunction()
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

        $mockInstructionNew = $this->getMockBuilder(TestAssemblyInstruction::class)
                                   ->addMethods(['executeOperand1'])
                                   ->getMock();

        $mockInstructionNew->expects($this->once())
                           ->method('executeOperand1')
                           ->willReturn(false);

        $simulator->registerInstructions($mockInstructionNew, [
            0x01 => [$mockInstructionNew, 'executeOperand1'],
        ]);

        $simulator->setCodeBuffer("\x01");
        $simulator->simulate();
    }

    public function testRexOnLongModeIsRecorded()
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstruction = $this->createMock(AssemblyInstruction::class);
        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertEquals(0x48, $simulator->getRex());
            $this->assertEquals([0x48], $simulator->getPrefixes());
            return true;
        };

        $simulator->registerInstructions($mockInstruction, [
            0x01 => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x48\x01");
        $simulator->simulate();
    }

    public function testRexOnProtectedModeThrowsException()
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $simulator->setCodeBuffer("\x40\x90");

        $this->expectException(\RuntimeException::class);
        $simulator->simulate();
    }

    public function testRexOnRealModeThrowsException()
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setCodeBuffer("\x40\x90");

        $this->expectException(\RuntimeException::class);
        $simulator->simulate();
    }

    public function testOp66IsRecordedAsPrefixInLongMode()
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

    public function testOp66IsRecordedAsPrefixInProtectedMode()
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

    public function testOp66IsIgnoredAsPrefixInRealMode()
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setCodeBuffer("\x66");

        $this->expectException(\OutOfBoundsException::class);
        $simulator->simulate();
    }

    public function testOp67IsRecordedAsPrefixInLongMode()
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

    public function testOp67IsRecordedAsPrefixInProtectedMode()
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

    public function testOp67IsIgnoredAsPrefixInRealMode()
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->setCodeBuffer("\x67");

        $this->expectException(\OutOfBoundsException::class);
        $simulator->simulate();
    }

    public function testTwoByteInstructionIsNotCalledInRealMode()
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

        $this->expectException(\OutOfBoundsException::class);
        $simulator->simulate();
    }

    public function testTwoByteInstructionIsCalledInProtectedMode()
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $mockInstruction = $this->createMock(TestAssemblyInstruction::class);
        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue(true);
            return true;
        };

        $simulator->registerInstructions($mockInstruction, [
            0x0F01  => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x0F\x01");
        $simulator->simulate();
    }

    public function testTwoByteInstructionIsCalledInLongMode()
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $mockInstruction = $this->createMock(TestAssemblyInstruction::class);
        $mockFunction = function () use ($simulator) {
            $simulator->advanceInstructionPointer(1);
            $this->assertTrue(true);
            return true;
        };

        $simulator->registerInstructions($mockInstruction, [
            0x0F01  => $mockFunction,
        ]);

        $simulator->setCodeBuffer("\x0F\x01");
        $simulator->simulate();
    }

    /**
     * @depends testOp66IsRecordedAsPrefixInLongMode
     * @depends testOp67IsRecordedAsPrefixInLongMode
     * @depends testTwoByteInstructionIsCalledInLongMode
     * @depends testRexOnLongModeIsRecorded
     */
    public function testGetPrefixReturnsListOfPrefixes()
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

    public function testRexPrefixTreatedAsInstructionAfterTwoBytePrefix()
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
