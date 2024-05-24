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
            [Simulator::LONG_MODE, 0x49, 0x00, Register::RSP, Register::R8, 23452, "\x58"],
            [Simulator::LONG_MODE, 0x49, 0x00, Register::RSP, Register::R9, 25234, "\x59"],
            [Simulator::LONG_MODE, 0x49, 0x00, Register::RSP, Register::R10, 98034, "\x5A"],
            [Simulator::LONG_MODE, 0x49, 0x00, Register::RSP, Register::R11, 897696, "\x5B"],
            [Simulator::LONG_MODE, 0x49, 0x00, Register::RSP, Register::R12, 87886, "\x5C"],
            [Simulator::LONG_MODE, 0x49, 0x00, Register::RSP, Register::R13, 234245, "\x5D"],
            [Simulator::LONG_MODE, 0x49, 0x00, Register::RSP, Register::R14, 5254356, "\x5E"],
            [Simulator::LONG_MODE, 0x49, 0x00, Register::RSP, Register::R15, 524534, "\x5F"],
            [Simulator::LONG_MODE, 0x48, 0x00, Register::RSP, Register::RAX, 64353, "\x58"],
            [Simulator::LONG_MODE, 0x48, 0x00, Register::RSP, Register::RCX, 1221455, "\x59"],
            [Simulator::LONG_MODE, 0x48, 0x00, Register::RSP, Register::RDX, 232526, "\x5A"],
            [Simulator::LONG_MODE, 0x48, 0x00, Register::RSP, Register::RBX, 75345, "\x5B"],
            [Simulator::LONG_MODE, 0x48, 0x00, Register::RSP, Register::RBP, 754634, "\x5D"],
            [Simulator::LONG_MODE, 0x48, 0x00, Register::RSP, Register::RSI, 234252, "\x5E"],
            [Simulator::LONG_MODE, 0x48, 0x00, Register::RSP, Register::RDI, 754634, "\x5F"],
            [Simulator::LONG_MODE, 0x00, 0x00, Register::RSP, Register::EAX, 21414, "\x58"],
            [Simulator::LONG_MODE, 0x00, 0x00, Register::RSP, Register::ECX, 55235, "\x59"],
            [Simulator::LONG_MODE, 0x00, 0x00, Register::RSP, Register::EDX, 55234, "\x5A"],
            [Simulator::LONG_MODE, 0x00, 0x00, Register::RSP, Register::EBX, 75464, "\x5B"],
            [Simulator::LONG_MODE, 0x00, 0x00, Register::RSP, Register::EBP, 352366, "\x5D"],
            [Simulator::LONG_MODE, 0x00, 0x00, Register::RSP, Register::ESI, 3462365, "\x5E"],
            [Simulator::LONG_MODE, 0x00, 0x00, Register::RSP, Register::EDI, 745677, "\x5F"],
            [Simulator::LONG_MODE, 0x00, 0x66, Register::RSP, Register::AX, 765785, "\x58"],
            [Simulator::LONG_MODE, 0x00, 0x66, Register::RSP, Register::CX, 2342, "\x59"],
            [Simulator::LONG_MODE, 0x00, 0x66, Register::RSP, Register::DX, 552342, "\x5A"],
            [Simulator::LONG_MODE, 0x00, 0x66, Register::RSP, Register::BX, 41412, "\x5B"],
            [Simulator::LONG_MODE, 0x00, 0x66, Register::RSP, Register::BP, 75463, "\x5D"],
            [Simulator::LONG_MODE, 0x00, 0x66, Register::RSP, Register::SI, 532464, "\x5E"],
            [Simulator::LONG_MODE, 0x00, 0x66, Register::RSP, Register::DI, 653245, "\x5F"],
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
        $opcode,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('getPrefixes')
                  ->willReturn([$prefixValue]);

        $simulator->method('readStackAt')
                  ->willReturn($expected);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($opcode);

        $simulator->method('readRegister')
                  ->willReturn(2);

        // This is where our value is saved, thus asserted for.
        $simulator->method('writeRegister')
                  ->willReturnCallback(function ($register, $value) use (
                      $expected,
                      $stackPointerRegister,
                      $destinationRegister,
                  ) {
                      if (4 === $register['offset']) {
                          // Ensure we are using the correct stack pointer
                          $this->assertEquals($stackPointerRegister, $register);
                      } elseif ($register === $destinationRegister) {
                          // Ensure we have popped the correct value into the register
                          $this->assertEquals($expected, $value);
                      }
                  });

        $instruction = new Pop();
        $instruction->setSimulator($simulator);

        $instruction->executeOperand5x();
    }
}
