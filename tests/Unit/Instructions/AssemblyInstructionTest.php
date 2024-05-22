<?php

namespace shanept\AssemblySimulatorTests\Unit\Instructions;

use ReflectionMethod;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulatorTests\Fakes\TestAssemblyInstruction;

class AssemblyInstructionTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    public function testSetSimulatorAttemptsToRegisterInstruction()
    {
        $simulator = $this->getMockSimulator();
        $instruction = new TestAssemblyInstruction();

        $simulator->expects($this->once())
                  ->method('registerInstructions')
                  ->with($instruction, []);

        $instruction->setSimulator($simulator);
    }

    public function testParseAddressThrowsExceptionOnInvalidRmByte()
    {
        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($this->getMockSimulator());

        $method = new ReflectionMethod($instruction, "parseAddress");

        $this->expectException(\OutOfRangeException::class);
        $method->invoke($instruction, ["rm" => 1]);
    }

    public function testParseAddressThrowsExceptionOnRealModeRipAddress()
    {
        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($this->getMockSimulator());

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");

        $this->expectException(\OutOfRangeException::class);
        $parseAddress->invoke($instruction, ["rm" => 5]);
    }

    public function testParseAddressAcceptsRipAddressOnLongMode()
    {
        $this->expectNotToPerformAssertions();

        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xe0\x17\x19\x0F")
                  ->with(4);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");

        $byte = [
            "mod" => 0,
            "reg" => 0b111,
            "rm" => 0b101,
        ];

        $parseAddress->invoke($instruction, $byte);
    }

    public function testParseAddressAcceptsSibAddressOnProtectedMode()
    {
        $this->expectNotToPerformAssertions();

        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\x0c")
                  ->with(1);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('getPrefix')
                  ->willReturn(false);

        $simulator->method('getRawRegisters')
                  ->willReturn(array_fill(0, 8, 0));

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");

        // mov [eax+ebx*4],ecx
        $simulator->setCodeBuffer("\x89\x0c\x98");
        $simulator->setInstructionPointer(2);

        $byte = [
            "mod" => 0,
            "reg" => 0b1,
            "rm" => 0b100,
        ];

        $parseAddress->invoke($instruction, $byte);
    }
}
