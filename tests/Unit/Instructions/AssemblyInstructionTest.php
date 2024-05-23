<?php

namespace shanept\AssemblySimulatorTests\Unit\Instructions;

use ReflectionMethod;
use shanept\AssemblySimulator\Register;
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

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertEquals(0xF1917E5, $address->getAddress());
    }

    public function testParseAddressAcceptsSibAddressOnProtectedMode()
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        // mov [eax+ebx*4],ecx
        // 0x89 0x0C 0x98
        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register) {
                      switch ($register) {
                          case Register::EAX:
                              return 54923;
                          case Register::EBX:
                              return 426;
                      }

                      $this->fail('Incorrect register ' . $register['name']);
                  });

        $simulator->method('getCodeAtInstruction')
                  ->with(1)
                  ->willReturn("\x98");

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");

        $byte = [
            "mod" => 0,
            "reg" => 0b1,
            "rm" => 0b100,
        ];

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertEquals(56629, $address->getAddress());
    }

    public function testParseAddressAcceptsSibAddressWithExtendedScaleOnLongMode()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov [rax+rbx*4],ecx
        // 0x89 0x0C 0xA0
        $simulator->method('getRex')
                  ->willReturn(0x4A);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register) {
                      switch ($register) {
                          case Register::RAX:
                              return 54925;
                          case Register::R12:
                              return 428;
                      }

                      $this->fail('Incorrect register ' . $register['name']);
                  });

        $simulator->method('getCodeAtInstruction')
                  ->with(1)
                  ->willReturn("\xA0");

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");

        $byte = [
            "mod" => 0,
            "reg" => 0b1,
            "rm" => 0b100,
        ];

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertEquals(56639, $address->getAddress());
    }

    public function testParseAddressWithSibDisp32OverrideInProtectedMode()
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->method('getInstructionPointer')
                  ->willReturn(3);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\x25");

        $simulator->expects($this->once())
                  ->method('getCodeBuffer')
                  ->willReturn("\x28\x00\x00\x00")
                  ->with(4, 4);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");

        $byte = [
            "mod" => 0,
            "reg" => 0,
            "rm" => 0b100,
        ];

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertEquals(48, $address->getAddress());
        $this->assertEquals(5, $address->getDisplacement());
    }

    public function testParseAddressWithSibEbpInProtectedMode()
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('getPrefix')
                  ->willReturn(0);

        $simulator->expects($this->once())
                  ->method('readRegister')
                  ->willReturn(69)
                  ->with(Register::EBP);

        $simulator->method('getInstructionPointer')
                  ->willReturn(3);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\x25");

        $simulator->expects($this->never())
                  ->method('getCodeBuffer');

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");

        $byte = [
            "mod" => 1,
            "reg" => 0,
            "rm" => 0b100,
        ];

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertEquals(73, $address->getAddress());
        $this->assertEquals(1, $address->getDisplacement());
    }
}
