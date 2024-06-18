<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Flags;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\ExclusiveOr;

class ExclusiveOrTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    public static function xorRegisterAgainstItselfIsEmptyDataProvider()
    {
        return [
            // REX.RB xor r9d,r9d
            // 0x45 0x31 0xC9
            [Simulator::LONG_MODE, 0x45, 0, Register::R9D, 1234, "\xC9"],

            // REX.WRB xor r9,r9
            // 0x4D 0x31 0xC9
            [Simulator::LONG_MODE, 0x4D, 0, Register::R9, 1234, "\xC9"],

            // 0x66 REX.RB xor r9w,r9w
            // 0x66 0x45 0x31 0xC9
            [Simulator::LONG_MODE, 0x45, 0x66, Register::R9W, 1234, "\xC9"],
        ];
    }

    public function testXor30RegisterAgainstItselfIsEmpty()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('readRegister')
                  ->willReturn(1234)
                  ->with(Register::R9B);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R9B, 0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xC9");

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand30();
    }

    /**
     * @dataProvider xorRegisterAgainstItselfIsEmptyDataProvider
     */
    public function testXor31RegisterAgainstItselfIsEmpty(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $register,
        $regValue,
        $instruction,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->method('readRegister')
                  ->willReturn($regValue)
                  ->with($register);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($register, 0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($instruction);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand31();
    }

    public function testXor32RegisterAgainstItselfIsEmpty()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('readRegister')
                  ->willReturn(1234)
                  ->with(Register::R9B);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R9B, 0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xC9");

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand32();
    }

    /**
     * @dataProvider xorRegisterAgainstItselfIsEmptyDataProvider
     */
    public function testXor33RegisterAgainstItselfIsEmpty(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $register,
        $regValue,
        $instruction,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->method('readRegister')
                  ->willReturn($regValue)
                  ->with($register);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($register, 0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($instruction);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand33();
    }

    public static function twoRegistersDataProvider()
    {
        return [
            // REX.RB xor r8d,r9d
            // 0x45 0x31 0xC8
            [Simulator::LONG_MODE, 0x45, 0, Register::R8D, 4321, Register::R9D, 1234, "\xC8"],

            // REX.WRB xor r8,r9
            // 0x4D 0x31 0xC8
            [Simulator::LONG_MODE, 0x4D, 0, Register::R8, 9374, Register::R9, 4527, "\xC8"],

            // 0x66 REX.RB xor r8w,r9w
            // 0x66 0x4D 0x31 0xC8
            [Simulator::LONG_MODE, 0x4D, 0x66, Register::R8W, 9374, Register::R9W, 4527, "\xC8"],
        ];
    }

    /**
     * @dataProvider twoRegistersDataProvider
     */
    public function testXor31TwoRegisters(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $firstRegister,
        $firstRegValue,
        $secondRegister,
        $secondRegValue,
        $instruction,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);
        $simulator = $this->mockSimulatorRegisters($simulator, [
            $firstRegister['offset'] => $firstRegValue,
            $secondRegister['offset'] => $secondRegValue,
        ]);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($instruction);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand31();

        $expected = $firstRegValue ^ $secondRegValue;
        $this->assertEquals($expected, $simulator->readRegister($firstRegister));
    }

    /**
     * @dataProvider twoRegistersDataProvider
     */
    public function testXor33TwoRegisters(
        $simulatorMode,
        $rexValue,
        $prefixValue,
        $firstRegister,
        $firstRegValue,
        $secondRegister,
        $secondRegValue,
        $instruction,
    ) {
        $simulator = $this->getMockSimulator($simulatorMode);
        $simulator = $this->mockSimulatorRegisters($simulator, [
            $firstRegister['offset'] => $firstRegValue,
            $secondRegister['offset'] => $secondRegValue,
        ]);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($instruction);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand33();

        $expected = $secondRegValue ^ $firstRegValue;
        $this->assertEquals($expected, $simulator->readRegister($secondRegister));
    }

    public function testXorClearsFlagsAfterExecution()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        /**
         * The following function implements the verification logic for the
         * parameter calls on the setFlags function. We expect the following
         * calls:
         *
         * setFlags(Flags::OF, 0)
         * setFlags(Flags::CF, 0)
         * setFlags(Flags::SF, 0)
         * setFlags(Flags::ZF, 1)
         * setFlags(Flags::FF, 1)
         */
        $flagVerifier = function ($flag, $value) {
            $expected = [
                Flags::OF => 0,
                Flags::CF => 0,
                Flags::SF => 0,
                Flags::ZF => 1,
                Flags::PF => 1,
            ];

            $name = '<UNKNOWN>';

            switch ($flag) {
                case Flags::OF:
                    $name = 'OF';
                    break;
                case Flags::CF:
                    $name = 'CF';
                    break;
                case Flags::SF:
                    $name = 'SF';
                    break;
                case Flags::ZF:
                    $name = 'ZF';
                    break;
                case Flags::PF:
                    $name = 'PF';
                    break;
            }

            $message = sprintf('Assertion for setFlags on Flags::%s:', $name);
            $this->assertEquals($expected[$flag], $value, $message);
        };

        $simulator->expects($this->exactly(5))
                  ->method('setFlag')
                  ->willReturnCallback($flagVerifier);

        // REX.RB xor r9 r9
        // 0x45 0x31 0xC9
        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xC9");

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand31();
    }

    public function testXorWithIncorrectModByteFails()
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);
        $simulator = $this->mockSimulatorRegisters($simulator, [
            Register::R9D['offset'] => 1234,
            Register::R8D['offset'] => 4321,
        ]);

        // REX.RB xor r9d,r9d, (mod 2 - incorrect)
        // 0x45 0x31 0x89 (mod 2 - incorrect)
        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\x89");

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $this->expectException(\RuntimeException::class);
        $xor->executeOperand31();
    }
}
