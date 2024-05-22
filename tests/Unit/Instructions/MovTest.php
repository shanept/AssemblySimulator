<?php

namespace shanept\AssemblySimulatorTests\Unit\Instructions;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\Move;

class MovTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    public static function movLoadsImmediate32bitValueDataProvider()
    {
        return [
            [Simulator::LONG_MODE, 0, 0, "\xB8", "\x01\x20\x20\x00", Register::EAX, 0x202001],
            [Simulator::LONG_MODE, 0, 0, "\xB9", "\x01\x00\x20\x00", Register::ECX, 0x200001],
            [Simulator::LONG_MODE, 0, 0, "\xBA", "\x01\x04\x20\x00", Register::EDX, 0x200401],
            [Simulator::LONG_MODE, 0, 0, "\xBB", "\x01\x00\x20\x00", Register::EBX, 0x200001],
            [Simulator::LONG_MODE, 0, 0, "\xBC", "\x01\x03\x20\x00", Register::ESP, 0x200301],
            [Simulator::LONG_MODE, 0, 0, "\xBD", "\x71\x00\x20\x00", Register::EBP, 0x200071],
            [Simulator::LONG_MODE, 0, 0, "\xBE", "\x01\x00\x20\x00", Register::ESI, 0x200001],
            [Simulator::LONG_MODE, 0, 0, "\xBF", "\x01\x00\x20\x10", Register::EDI, 0x10200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xB8", "\x01\x20\x20\x00\x00\x00\x00\x00", Register::RAX, 0x202001],
            [Simulator::LONG_MODE, 0x48, 0, "\xB9", "\x01\x00\x20\x00\x00\x00\x00\x00", Register::RCX, 0x200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xBA", "\x01\x04\x20\x00\x00\x00\x00\x00", Register::RDX, 0x200401],
            [Simulator::LONG_MODE, 0x48, 0, "\xBB", "\x01\x00\x20\x00\x00\x00\x00\x00", Register::RBX, 0x200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xBC", "\x01\x03\x20\x00\x00\x00\x00\x00", Register::RSP, 0x200301],
            [Simulator::LONG_MODE, 0x48, 0, "\xBD", "\x71\x00\x20\x00\x00\x00\x00\x00", Register::RBP, 0x200071],
            [Simulator::LONG_MODE, 0x48, 0, "\xBE", "\x01\x00\x20\x00\x00\x00\x00\x00", Register::RSI, 0x200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xBF", "\x01\x00\x20\x10\x00\x00\x00\x00", Register::RDI, 0x10200001],
        ];
    }

    /**
     * @dataProvider movLoadsImmediate32bitValueDataProvider
     */
    public function testMovLoadsImmediate32bitValue(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $opcode,
        $address,
        $register,
        $expected
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        // mov ecx,0x200001
        // 0xB9 0x01 0x00 0x20 0x00
        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('getPrefix')
                  ->willReturn($prefixValue);

        $values = [$address, $opcode];

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function($length) use(&$values) {
                      return array_pop($values);
                  });

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($register, $expected);

        $move = new Move();
        $move->setSimulator($simulator);

        $move->executeOperandBx();
    }

    public function testMov8bLoadsMemoryAddress()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov rdx,QWORD PTR [rip+0x2bbd62]
        // 0x48 0x8B 0x15 0x62 0xBD 0x2B 0x00
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function($length) {
                      $values = [
                          1 => "\x15",
                          4 => "\x62\x8D\x2B\x00",
                      ];

                      return $values[$length];
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturn(3);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::RDX, 0x2B8D69);

        $move = new Move();
        $move->setSimulator($simulator);
        $move->executeOperand8b();
    }

    public function testMov89OnRegisterValue()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov rcx,rdx
        // 0x48 0x89 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1");

        $simulator->method('readRegister')
                  ->with(Register::RDX)
                  ->willReturn(690);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::RCX, 690);

        $move = new Move();
        $move->setSimulator($simulator);

        $move->executeOperand89();
    }

    public function testMov89OnRexRegisterValue()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov r9,r10
        // 0x4D 0x89 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x4D);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1");

        $simulator->method('readRegister')
                  ->with(Register::R10)
                  ->willReturn(691);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R9, 691);

        $move = new Move();
        $move->setSimulator($simulator);

        $move->executeOperand89();
    }

    public function testMov8bOnRegisterValue()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov rdx,rcx
        // 0x48 0x8B 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1");

        $simulator->method('readRegister')
                  ->with(Register::RCX)
                  ->willReturn(693);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::RDX, 693);

        $move = new Move();
        $move->setSimulator($simulator);

        $move->executeOperand8b();
    }

    public function testMov8bOnRexRegisterValue()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov r10,r9
        // 0x4D 0x8B 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x4D);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1");

        $simulator->method('readRegister')
                  ->with(Register::R9)
                  ->willReturn(694);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R10, 694);

        $move = new Move();
        $move->setSimulator($simulator);

        $move->executeOperand8b();
    }
}
