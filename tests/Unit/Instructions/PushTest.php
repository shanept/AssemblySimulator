<?php

namespace shanept\AssemblySimulatorTests\Unit\Instructions;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\Push;

class PushTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    public static function pushOnStack5xDataProvider()
    {
        return [
            [Simulator::LONG_MODE, 0x49, 0, Register::RSP, 32, Register::R8, 'abnmhgc', "\x50"],
            [Simulator::LONG_MODE, 0x49, 0, Register::RSP, 42, Register::R9, 'abjmc', "\x51"],
            [Simulator::LONG_MODE, 0x49, 0, Register::RSP, 52, Register::R10, 'abnbmc', "\x52"],
            [Simulator::LONG_MODE, 0x49, 0, Register::RSP, 62, Register::R11, 'amjbc', "\x53"],
            [Simulator::LONG_MODE, 0x49, 0, Register::RSP, 72, Register::R12, 'abmuyc', "\x54"],
            [Simulator::LONG_MODE, 0x49, 0, Register::RSP, 82, Register::R13, 'aukjbc', "\x55"],
            [Simulator::LONG_MODE, 0x49, 0, Register::RSP, 92, Register::R14, 'abgerc', "\x56"],
            [Simulator::LONG_MODE, 0x49, 0, Register::RSP, 10, Register::R15, 'abbxvc', "\x57"],
            [Simulator::LONG_MODE, 0x48, 0, Register::RSP, 11, Register::RAX, 'abagc', "\x50"],
            [Simulator::LONG_MODE, 0x48, 0, Register::RSP, 12, Register::RCX, 'ab443c', "\x51"],
            [Simulator::LONG_MODE, 0x48, 0, Register::RSP, 13, Register::RDX, 'aretebc', "\x52"],
            [Simulator::LONG_MODE, 0x48, 0, Register::RSP, 14, Register::RBX, 'abg6c', "\x53"],
            [Simulator::LONG_MODE, 0x48, 0, Register::RSP, 15, Register::RBP, 'abc', "\x55"],
            [Simulator::LONG_MODE, 0x48, 0, Register::RSP, 16, Register::RSI, 'ajtybc', "\x56"],
            [Simulator::LONG_MODE, 0x48, 0, Register::RSP, 17, Register::RDI, 'agrbc', "\x57"],
            [Simulator::LONG_MODE, 0, 0, Register::RSP, 18, Register::EAX, 'abmcc', "\x50"],
            [Simulator::LONG_MODE, 0, 0, Register::RSP, 19, Register::ECX, 'anbbc', "\x51"],
            [Simulator::LONG_MODE, 0, 0, Register::RSP, 20, Register::EDX, 'abdsbc', "\x52"],
            [Simulator::LONG_MODE, 0, 0, Register::RSP, 21, Register::EBX, 'abcsh', "\x53"],
            [Simulator::LONG_MODE, 0, 0, Register::RSP, 22, Register::EBP, 'abfhsdc', "\x55"],
            [Simulator::LONG_MODE, 0, 0, Register::RSP, 23, Register::ESI, 'abhrc', "\x56"],
            [Simulator::LONG_MODE, 0, 0, Register::RSP, 24, Register::EDI, 'abrtc', "\x57"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, 25, Register::AX, 'abwec', "\x50"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, 26, Register::CX, 'abcsf', "\x51"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, 27, Register::DX, 'abcvxc', "\x52"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, 28, Register::BX, 'adssbc', "\x53"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, 29, Register::BP, 'abvac', "\x55"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, 30, Register::SI, 'aabc', "\x56"],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, 31, Register::DI, 'abc', "\x57"],
        ];
    }

    /**
     * @dataProvider pushOnStack5xDataProvider
     */
    public function testPushOnStack5x(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $stackPointer,
        $stackPosition,
        $expectedRegister,
        $regValue,
        $opcode,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('getPrefix')
                  ->willReturn($prefixValue);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($opcode);

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register, $size) use (
                      $stackPointer,
                      $stackPosition,
                      $expectedRegister,
                      $regValue
                  ) {
                      if (4 === $register['offset']) {
                          $this->assertEquals($stackPointer, $register);
                          return $stackPosition;
                      } else {
                          $this->assertEquals($expectedRegister, $register);
                          return $regValue;
                      }
                  });

        $simulator->expects($this->once())
                  ->method('setStackAt')
                  ->with($stackPosition + 1, $regValue);

        $instruction = new Push();
        $instruction->setSimulator($simulator);

        $instruction->executeOperand5x();
    }

    public static function pushOnStack68DataProvider()
    {
        return [
            [Simulator::LONG_MODE, 0x48, 0, Register::RSP, 33, "\x34\x00\x42\x00\x59\x00\x12\x00", 0x12005900420034],
            [Simulator::LONG_MODE, 0, 0, Register::RSP, 3, "\x84\x12\x58\x12", 0x12581284],
            [Simulator::LONG_MODE, 0, 0x66, Register::RSP, 7, "\x36\x45", 0x4536],
        ];
    }

    /**
     * @dataProvider pushOnStack68DataProvider
     */
    public function testPushOnStack68(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $stackPointer,
        $stackPosition,
        $immediate,
        $expected,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('getPrefix')
                  ->willReturn($prefixValue);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($immediate);

        $simulator->method('readRegister')
                  ->willReturn($stackPosition)
                  ->with($stackPointer);

        $simulator->expects($this->once())
                  ->method('setStackAt')
                  ->with($stackPosition + 1, $expected);

        $instruction = new Push();
        $instruction->setSimulator($simulator);

        $instruction->executeOperand68();
    }

    public static function pushOnStack6aDataProvider()
    {
        return [
            [Simulator::LONG_MODE, 0, 0, Register::RSP, 3, "\x43", 0x43],
            [Simulator::PROTECTED_MODE, 0, 0, Register::ESP, 92, "\x42", 0x42],
            [Simulator::REAL_MODE, 0, 0, Register::SP, 12, "\x59", 0x59],
        ];
    }

    /**
     * @dataProvider pushOnStack6aDataProvider
     */
    public function testPushOnStack6a(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $stackPointer,
        $stackPosition,
        $immediate,
        $expected,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('getPrefix')
                  ->willReturn($prefixValue);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($immediate);

        $simulator->method('readRegister')
                  ->willReturn($stackPosition)
                  ->with($stackPointer);

        $simulator->expects($this->once())
                  ->method('setStackAt')
                  ->with($stackPosition + 1, $expected);

        $instruction = new Push();
        $instruction->setSimulator($simulator);

        $instruction->executeOperand6a();
    }
}
