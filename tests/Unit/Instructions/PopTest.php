<?php

namespace shanept\AssemblySimulatorTests\Unit\Instructions;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\Pop;

class PopTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    public static function popOffStackDataProvider()
    {
        return [
            [Simulator::LONG_MODE, 0x49, false, Register::RSP, Register::R8, 'ghi', "\x58"],
            [Simulator::LONG_MODE, 0x49, false, Register::RSP, Register::R9, 'jkl', "\x59"],
            [Simulator::LONG_MODE, 0x49, false, Register::RSP, Register::R10, 'mno', "\x5A"],
            [Simulator::LONG_MODE, 0x49, false, Register::RSP, Register::R11, 'pqr', "\x5B"],
            [Simulator::LONG_MODE, 0x49, false, Register::RSP, Register::R12, 'pqr', "\x5C"],
            [Simulator::LONG_MODE, 0x49, false, Register::RSP, Register::R13, 'stu', "\x5D"],
            [Simulator::LONG_MODE, 0x49, false, Register::RSP, Register::R14, 'vwx', "\x5E"],
            [Simulator::LONG_MODE, 0x49, false, Register::RSP, Register::R15, 'yz', "\x5F"],
            [Simulator::LONG_MODE, 0x48, false, Register::RSP, Register::RAX, 'ghi', "\x58"],
            [Simulator::LONG_MODE, 0x48, false, Register::RSP, Register::RCX, 'jkl', "\x59"],
            [Simulator::LONG_MODE, 0x48, false, Register::RSP, Register::RDX, 'mno', "\x5A"],
            [Simulator::LONG_MODE, 0x48, false, Register::RSP, Register::RBX, 'pqr', "\x5B"],
            [Simulator::LONG_MODE, 0x48, false, Register::RSP, Register::RBP, 'stu', "\x5D"],
            [Simulator::LONG_MODE, 0x48, false, Register::RSP, Register::RSI, 'vwx', "\x5E"],
            [Simulator::LONG_MODE, 0x48, false, Register::RSP, Register::RDI, 'yz', "\x5F"],
            [Simulator::LONG_MODE, 0, false, Register::RSP, Register::EAX, 'ghi', "\x58"],
            [Simulator::LONG_MODE, 0, false, Register::RSP, Register::ECX, 'jkl', "\x59"],
            [Simulator::LONG_MODE, 0, false, Register::RSP, Register::EDX, 'mno', "\x5A"],
            [Simulator::LONG_MODE, 0, false, Register::RSP, Register::EBX, 'pqr', "\x5B"],
            [Simulator::LONG_MODE, 0, false, Register::RSP, Register::EBP, 'stu', "\x5D"],
            [Simulator::LONG_MODE, 0, false, Register::RSP, Register::ESI, 'vwx', "\x5E"],
            [Simulator::LONG_MODE, 0, false, Register::RSP, Register::EDI, 'yz', "\x5F"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, Register::AX, 'ghi', "\x58"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, Register::CX, 'jkl', "\x59"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, Register::DX, 'mno', "\x5A"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, Register::BX, 'pqr', "\x5B"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, Register::BP, 'stu', "\x5D"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, Register::SI, 'vwx', "\x5E"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, Register::DI, 'yz', "\x5F"],
        ];
    }

    /**
     * @dataProvider popOffStackDataProvider
     */
    public function testpopOffStack(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $stackPointerRegister,
        $destinationRegister,
        $expected,
        $opcode
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('getPrefix')
                  ->willReturn($prefixValue);

        $simulator->method('readStackAt')
                  ->willReturn($expected);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($opcode);

        $simulator->method('readRegister')
                  ->willReturn(2);

        // This is where our value is saved, thus asserted for.
        $simulator->method('writeRegister')
                  ->willReturnCallback(function($register, $value) use (
                      $expected,
                      $stackPointerRegister,
                      $destinationRegister
                  ) {
                      if (4 === $register['offset']) {
                          // Ensure we are using the correct stack pointer
                          $this->assertEquals($stackPointerRegister, $register);
                      } else if ($register === $destinationRegister) {
                          // Ensure we have popped the correct value into the register
                          $this->assertEquals($expected, $value);
                      }
                  });

        $instruction = new Pop();
        $instruction->setSimulator($simulator);

        $instruction->executeOperand5x();
    }
}
