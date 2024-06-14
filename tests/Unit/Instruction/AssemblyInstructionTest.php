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

    public static function parseAddressOnNonRipOrSibAddressDataProvider()
    {
        return [
            [Simulator::LONG_MODE,  null, Register::EBX, 849, "\x10", 1, 0, 3, 865],
            [Simulator::PROTECTED_MODE, null, Register::EBX, 849, "\x10\x00\x00\x00", 2, 0, 3, 865],
            [Simulator::REAL_MODE, 0x66, Register::EBX, 277938170, "\x10", 1, 0, 3, 277938186],
        ];
    }

    /**
     * @dataProvider parseAddressOnNonRipOrSibAddressDataProvider
     */
    public function testParseAddressOnNonRipOrSibAddress(
        $simulatorMode,
        $prefixValue,
        $register,
        $regValue,
        $addressBytes,
        $mod,
        $reg,
        $rm,
        $expect,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('readRegister')
                  ->willReturn($regValue)
                  ->with($register);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $prefixValue === $requested;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        if (1 === $mod) {
            $dispSize = 1;
        } elseif (2 === $mod) {
            $dispSize = 4;
        } else {
            $dispSize = 0;
        }

        $simulator->method('getCodeBuffer')
                  ->willReturn($addressBytes)
                  ->with(1, $dispSize);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $method = new ReflectionMethod($instruction, "parseAddress");

        $byte = compact('mod', 'reg', 'rm');
        $address = $method->invoke($instruction, $byte);

        $this->assertEquals($expect, $address->getAddress());
        $this->assertEquals($dispSize, $address->getDisplacement());
    }

    public static function parseAddressOnModRmDisp32AddressInProtectedModeDataProvider()
    {
        return [
            [Simulator::REAL_MODE, Register::BP],
            [Simulator::PROTECTED_MODE, Register::EBP],
        ];
    }

    /**
     * @dataProvider parseAddressOnModRmDisp32AddressInProtectedModeDataProvider
     */
    public function testParseAddressOnModRmDisp32AddressInProtectedMode($mode, $reg)
    {
        // mov edx, 0x10722a80
        // 0x8b 0x15 0x80 0x2a 0x72 0x10
        $simulator = $this->getMockSimulator($mode);

        $simulator->expects($this->never())
                  ->method('readRegister')
                  ->with($reg)
                  ->willReturn(100);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getInstructionPointer')
                  ->willReturn(2);

        $simulator->method('getCodeBuffer')
                  ->willReturn("\x80\x2a\x72\x10")
                  ->with(2, 4);

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $method = new ReflectionMethod($instruction, "parseAddress");

        $byte = [
            'mod' => 0,
            'reg' => 2,
            'rm' => 5,
        ];
        $address = $method->invoke($instruction, $byte);

        $this->assertEquals(0x10722a80, $address->getAddress());
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

    public static function parseSibAddressDataProvider()
    {
        return [
            // mov [eax+ebx*4],ecx
            // 0x89 0x0C 0x98
            [Simulator::PROTECTED_MODE, 0, null, Register::EBX, 426, Register::EAX, 54923, 0, 1, 4, "\x98", null, 56627],

            // mov [rax+r12*4],ecx
            // 0x89 0x0C 0xA0
            [Simulator::LONG_MODE, 0x4A, null, Register::R12, 428, Register::RAX, 54925, 0, 1, 4, "\xA0", null, 56637],

            // mov eax,[ebp+eax*4+0x31]
            // 0x8B 0x44 0x85 0x31
            [Simulator::PROTECTED_MODE, 0, null, Register::EAX, 12, Register::EBP, 498, 1, 0, 4, "\x85", "\x31", 595],

            // mov eax,[ebp+eax*2+0x40201030]
            // 0x8B 0x84 0x45 0x30 0x10 0x20 0x40
            [Simulator::PROTECTED_MODE, 0, null, Register::EAX, 12, Register::EBP, 498, 2, 0, 4, "\x45", "\x30\x10\x20\x40", 1075843642],
        ];
    }

    /**
     * @dataProvider parseSibAddressDataProvider
     */
    public function testParseSibAddress(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $scaleReg,
        $scaleVal,
        $indexReg,
        $indexVal,
        $mod,
        $reg,
        $rm,
        $sibByte,
        $dispByte,
        $expected,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $prefixValue === $requested;
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturn(2);

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register) use (
                      $scaleReg,
                      $scaleVal,
                      $indexReg,
                      $indexVal,
                  ) {
                      switch ($register) {
                          case $scaleReg:
                              return $scaleVal;
                          case $indexReg:
                              return $indexVal;
                      }

                      $this->fail('Incorrect register ' . $register['name']);
                  });

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($sibByte)
                  ->with(strlen($sibByte));

        $dispByteLen = 0;
        if (! is_null($dispByte)) {
            $dispByteLen = strlen($dispByte);
            $simulator->method('getCodeBuffer')
                      ->willReturn($dispByte)
                      ->with(3, $dispByteLen);
        }

        $instruction = new TestAssemblyInstruction();
        $instruction->setSimulator($simulator);

        $parseAddress = new ReflectionMethod($instruction, "parseAddress");

        $byte = [
            "mod" => $mod,
            "reg" => $reg,
            "rm" => $rm,
        ];

        $address = $parseAddress->invoke($instruction, $byte);
        $this->assertEquals($expected, $address->getAddress());
        $this->assertEquals(1 + $dispByteLen, $address->getDisplacement());
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
        $this->assertEquals(40, $address->getAddress());
        $this->assertEquals(5, $address->getDisplacement());
    }
}
