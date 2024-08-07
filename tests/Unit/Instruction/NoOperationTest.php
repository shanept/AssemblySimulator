<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use PHPUnit\Framework\TestCase;
use shanept\AssemblySimulator\Instruction\NoOperation;

/**
 * @covers shanept\AssemblySimulator\Instruction\NoOperation
 */
class NoOperationTest extends TestCase
{
    use MockSimulatorTrait;

    /**
     * @small
     */
    public function testNoOperationOperand90LeavesRegistersAndStackAlone(): void
    {
        $simulator = $this->getMockSimulator();

        $simulator->expects($this->never())
                  ->method('readRegister');

        $simulator->expects($this->never())
                  ->method('writeRegister');

        $simulator->expects($this->never())
                  ->method('getStack');

        $simulator->expects($this->never())
                  ->method('readStackAt');

        $simulator->expects($this->never())
                  ->method('writeStackAt');

        $simulator->expects($this->never())
                  ->method('clearStackAt');

        $simulator->expects($this->once())
                  ->method('advanceInstructionPointer')
                  ->with(1);

        $nop = new NoOperation();
        $nop->setSimulator($simulator);

        $this->assertTrue($nop->executeOperand90());
    }

    /**
     * @small
     */
    public function testNoOperationOperand0F1FLeavesRegistersAndStackAlone(): void
    {
        $simulator = $this->getMockSimulator();

        $simulator->expects($this->never())
                  ->method('readRegister');

        $simulator->expects($this->never())
                  ->method('writeRegister');

        $simulator->expects($this->never())
                  ->method('getStack');

        $simulator->expects($this->never())
                  ->method('readStackAt');

        $simulator->expects($this->never())
                  ->method('writeStackAt');

        $simulator->expects($this->never())
                  ->method('clearStackAt');

        $simulator->expects($this->atLeastOnce())
                  ->method('advanceInstructionPointer');

        $simulator->expects($this->once())
                  ->method('getCodeAtInstructionPointer')
                  ->willReturn("\xC0");

        $nop = new NoOperation();
        $nop->setSimulator($simulator);

        $this->assertTrue($nop->executeOperand0F1F());
    }

    /**
     * @small
     */
    public function testNoOperationOperand0F1FWithAddressLeavesRegistersAndStackAlone(): void
    {
        // 0x0F 0x1F 0x80 0x00 0x00 0x00 0x00
        // NOP [rax+0x0]
        $simulator = $this->getMockSimulator();

        $simulator->expects($this->never())
                  ->method('writeRegister');

        $simulator->expects($this->never())
                  ->method('getStack');

        $simulator->expects($this->never())
                  ->method('readStackAt');

        $simulator->expects($this->never())
                  ->method('writeStackAt');

        $simulator->expects($this->never())
                  ->method('clearStackAt');

        $iPointer = 1;

        $simulator->expects($this->atLeastOnce())
                  ->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturn(3);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstructionPointer')
                  ->willReturn("\x80")
                  ->with(1);

        $simulator->expects($this->once())
                  ->method('getCodeBuffer')
                  ->willReturn("\x00\x00\x00\x00")
                  ->with(3, 4);

        $nop = new NoOperation();
        $nop->setSimulator($simulator);

        $this->assertTrue($nop->executeOperand0F1F());
        $this->assertEquals(7, $iPointer);
    }
}
