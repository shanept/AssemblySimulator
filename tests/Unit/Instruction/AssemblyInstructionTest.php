<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use ReflectionMethod;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Address\RipAddress;
use shanept\AssemblySimulator\Address\ModRmAddress;
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

    public static function getAddressSizeDataProvider()
    {
        return [
            // No prefix
            [Simulator::REAL_MODE, 0, null, 16],
            [Simulator::PROTECTED_MODE, 0, null, 32],
            [Simulator::LONG_MODE, 0, null, 64],
            // 64-bit operand size
            [Simulator::LONG_MODE, 0x48, null, 64],

            // Prefix applied
            [Simulator::REAL_MODE, 0, 0x67, 32],
            [Simulator::PROTECTED_MODE, 0, 0x67, 16],
            [Simulator::LONG_MODE, 0, 0x67, 64],
            // 64-bit operand size
            [Simulator::LONG_MODE, 0x48, 0x67, 64],
        ];
    }

    /**
     * @dataProvider getAddressSizeDataProvider
     */
    public function testGetAddressSize(
        $simulatorMode,
        $rex,
        $prefix,
        $expected,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rex);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($check) use ($prefix) {
                      return $check === $prefix;
                  });

        $simulator->method('getLargestInstructionWidth')
                  ->willReturnCallback(function () use ($simulatorMode) {
                      switch ($simulatorMode) {
                          case Simulator::REAL_MODE:
                              return 16;
                          case Simulator::PROTECTED_MODE:
                              return 32;
                          case Simulator::LONG_MODE:
                              return 64;
                      }
                  });

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $fn = new ReflectionMethod($instruction, 'getAddressSize');

        $this->assertEquals($expected, $fn->invoke($instruction));
    }

    public function testParseAddressOnNonRipOrSibAddress()
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('readRegister')
                  ->willReturn(849)
                  ->with(Register::EBX);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $simulator->method('getCodeBuffer')
                  ->willReturn("\x10\x00\x00\x00")
                  ->with(1, 4);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $method = new ReflectionMethod($instruction, "parseAddress");

        $byte = ['mod' => 2, 'reg' => 0, 'rm' => 3];
        $address = $method->invoke($instruction, $byte);

        $this->assertEquals(865, $address->getAddress());
        $this->assertEquals(4, $address->getDisplacement());
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
        $this->assertInstanceOf(RipAddress::class, $address);
        $this->assertEquals(0xF1917E5, $address->getAddress());
    }

    public function testParseAddressLooksLikeRipOnProtectedModeReturnsModRmAddress()
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeBuffer')
                  ->willReturn("\xe0\x17\x19\x0F")
                  ->with(1, 4);

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
        $this->assertInstanceOf(ModRmAddress::class, $address);
        $this->assertEquals(0xF1917E0, $address->getAddress());
    }

    public function testParseAddressDoesNotUseRipOnModRmMod1()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getCodeBuffer')
                  ->willReturn("\xe0")
                  ->with(1, 1);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");

        $byte = [
            "mod" => 1,
            "reg" => 0b111,
            "rm" => 0b101,
        ];

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertNotInstanceOf(RipAddress::class, $address);
    }

    public function testParseAddressAcceptsSibAddressOnProtectedMode()
    {
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        // mov [eax+ebx*4],ecx
        // 0x89 0x0C 0x98
        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

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

        $simulator->method('hasPrefix')
                  ->willReturn(false);

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

        $simulator->method('hasPrefix')
                  ->willReturn(false);

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

        $simulator->method('hasPrefix')
                  ->willReturn(false);

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
