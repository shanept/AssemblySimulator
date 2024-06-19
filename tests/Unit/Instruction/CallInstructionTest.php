<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\CallInstruction;
use shanept\AssemblySimulatorTests\Fakes\TestAssemblyInstruction;

class CallInstructionTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    public function testCallInstructionParsesInput(): void
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $stringPointer = "\x10\x20\x30\x40";
        $binaryPointer = pack('V', $stringPointer);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($stringPointer)
                  ->with(4);

        $simulator->method('readRegister')
                  ->willReturn(128)
                  ->with(Register::ESP);

        $simulator->expects($this->once())
                  ->method('writeStackAt')
                  ->with(96, $binaryPointer);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::ESP, 96);

        $iPointer = 0;
        $simulator->expects($this->atLeastOnce())
                  ->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $call = new CallInstruction();
        $call->setSimulator($simulator);

        $this->assertTrue($call->executeOperandE8());

        $this->assertEquals(5, $iPointer);
    }

    public function testCallInstructionThrowsExceptionOnInvalidCallback(): void
    {
        $callback = $this->createMock(TestAssemblyInstruction::class);

        $callback->expects($this->never())
                 ->method('mockableCallback')
                 ->willReturnCallback(function ($address) {
                     $this->assertEquals(0x40302010, $address);
                 });

        $this->expectException(\LogicException::class);
        $call = new CallInstruction([null, 'mockableCallback']);
    }

    public function testCallInstructionParsesPositiveAddress(): void
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeAtInstruction')
                  ->with(4)
                  ->willReturn("\x10\x20\x30\x40");

        $simulator->method('getAddressBase')
                  ->willReturn(0xF3);

        $iPointer = 5;
        $simulator->expects($this->atLeastOnce())
                  ->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturnCallback(function () use ($iPointer) {
                      return $iPointer;
                  });


        /**
         * We are only using the TestAssemblyInstruction here because mocking
         * classes provide an easy method to create a custom function and ensure
         * it has been called.
         * If we do not ensure that the callback has in fact been called, this
         * test may pass if the callback is not called. We have to ensure the
         * callback is called, otherwise it is a failure of this test.
         */
        $callback = $this->createMock(TestAssemblyInstruction::class);

        $callback->expects($this->once())
                 ->method('mockableCallback')
                 ->willReturnCallback(function ($address): bool {
                     $this->assertEquals(0x40302108, $address);
                     return true;
                 });

        $call = new CallInstruction([$callback, 'mockableCallback']);
        $call->setSimulator($simulator);

        $this->assertTrue($call->executeOperandE8());
        $this->assertEquals(10, $iPointer);
    }

    public function testCallInstructionParsesNegativeAddress(): void
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeAtInstruction')
                  ->with(4)
                  ->willReturn("\x10\x20\x30\xC0");

        $simulator->method('getAddressBase')
                  ->willReturn(0xFF);

        $iPointer = 3;
        $simulator->expects($this->atLeastOnce())
                  ->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturnCallback(function () use ($iPointer) {
                      return $iPointer;
                  });

        /**
         * We are only using the TestAssemblyInstruction here because mocking
         * classes provide an easy method to create a custom function and ensure
         * it has been called.
         * If we do not ensure that the callback has in fact been called, this
         * test may pass if the callback is not called. We have to ensure the
         * callback is called, otherwise it is a failure of this test.
         */
        $callback = $this->createMock(TestAssemblyInstruction::class);

        $callback->expects($this->once())
                 ->method('mockableCallback')
                 ->willReturnCallback(function ($address): bool {
                     $this->assertEquals(-0x3FCFDEEE, $address);
                     return true;
                 });

        $call = new CallInstruction([$callback, 'mockableCallback']);
        $call->setSimulator($simulator);

        $this->assertTrue($call->executeOperandE8());
        $this->assertEquals(8, $iPointer);
    }
}
