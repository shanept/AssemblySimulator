<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\CallInstruction;
use shanept\AssemblySimulatorTests\Fakes\TestAssemblyInstruction;

class CallInstructionTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    public function testCallInstructionParsesInput()
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $stringPointer = "\x10\x20\x30\x40";
        $binaryPointer = pack('V', $stringPointer);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($stringPointer);

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

    public function testCallInstructionThrowsExceptionOnInvalidCallback()
    {
        $callback = $this->getMockBuilder(TestAssemblyInstruction::class)
                         ->addMethods(['addressCallback'])
                         ->getMock();

        $callback->expects($this->never())
                 ->method('addressCallback')
                 ->willReturnCallback(function ($address) {
                     $this->assertEquals(0x40302010, $address);
                 });

        $this->expectException(\LogicException::class);
        $call = new CallInstruction([null, 'addressCallback']);
    }

    public function testCallInstructionParsesPositiveAddress()
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\x10\x20\x30\x40");

        $simulator->method('getAddressBase')
                  ->willReturn(0xF3);

        $iPointer = 0;
        $simulator->expects($this->atLeastOnce())
                  ->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        /**
         * We are only using the TestAssemblyInstruction here because mocking
         * classes provide an easy method to create a custom function and ensure
         * it has been called.
         * If we do not ensure that the callback has in fact been called, this
         * test may pass if the callback is not called. We have to ensure the
         * callback is called, otherwise it is a failure of this test.
         */
        $callback = $this->getMockBuilder(TestAssemblyInstruction::class)
                         ->addMethods(['addressCallback'])
                         ->getMock();

        $callback->expects($this->once())
                 ->method('addressCallback')
                 ->willReturnCallback(function ($address) {
                     $this->assertEquals(0x40302103, $address);
                 });

        $call = new CallInstruction([$callback, 'addressCallback']);
        $call->setSimulator($simulator);

        $this->assertTrue($call->executeOperandE8());

        $this->assertEquals(5, $iPointer);
    }

    public function testCallInstructionParsesNegativeAddress()
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\x10\x20\x30\xC0");

        $simulator->method('getAddressBase')
                  ->willReturn(0xFF);

        $iPointer = 0;
        $simulator->expects($this->atLeastOnce())
                  ->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        /**
         * We are only using the TestAssemblyInstruction here because mocking
         * classes provide an easy method to create a custom function and ensure
         * it has been called.
         * If we do not ensure that the callback has in fact been called, this
         * test may pass if the callback is not called. We have to ensure the
         * callback is called, otherwise it is a failure of this test.
         */
        $callback = $this->getMockBuilder(TestAssemblyInstruction::class)
                         ->addMethods(['addressCallback'])
                         ->getMock();

        $callback->expects($this->once())
                 ->method('addressCallback')
                 ->willReturnCallback(function ($address) {
                     $this->assertEquals(-0x3FCFDEF1, $address);
                 });

        $call = new CallInstruction([$callback, 'addressCallback']);
        $call->setSimulator($simulator);

        $this->assertTrue($call->executeOperandE8());

        $this->assertEquals(5, $iPointer);
    }
}
