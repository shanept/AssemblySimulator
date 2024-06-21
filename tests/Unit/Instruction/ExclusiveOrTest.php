<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Flags;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\ExclusiveOr;

/**
 * @covers shanept\AssemblySimulator\Instruction\ExclusiveOr
 */
class ExclusiveOrTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    /**
     * @return array<int, array{int, int, int, RegisterObj, int, string}>
     */
    public static function xorRegisterAgainstItselfIsEmptyDataProvider(): array
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

    /**
     * @small
     */
    public function testXor30RegisterAgainstItselfIsEmpty(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->expects($this->exactly(2))
                  ->method('readRegister')
                  ->with(Register::R9B)
                  ->willReturn(1234);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R9B, 0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xC9")
                  ->with(1);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand30();
    }

    /**
     * @dataProvider xorRegisterAgainstItselfIsEmptyDataProvider
     * @small
     *
     * @param RegisterObj $register
     */
    public function testXor31RegisterAgainstItselfIsEmpty(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        array $register,
        int $regValue,
        string $instruction,
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->exactly(2))
                  ->method('readRegister')
                  ->willReturn($regValue)
                  ->with($register);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($register, 0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn($instruction)
                  ->with(1);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand31();
    }

    /**
     * @small
     */
    public function testXor32RegisterAgainstItselfIsEmpty(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->expects($this->exactly(2))
                  ->method('readRegister')
                  ->with(Register::R9B)
                  ->willReturn(1234);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R9B, 0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xC9")
                  ->with(1);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand32();
    }

    /**
     * @dataProvider xorRegisterAgainstItselfIsEmptyDataProvider
     * @small
     *
     * @param RegisterObj $register
     */
    public function testXor33RegisterAgainstItselfIsEmpty(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        array $register,
        int $regValue,
        string $instruction,
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->exactly(2))
                  ->method('readRegister')
                  ->with($register)
                  ->willReturn($regValue);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($register, 0);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstruction')
                  ->willReturn($instruction)
                  ->with(1);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand33();
    }

    /**
     * @return array<int, array{int, int, int, RegisterObj, int, RegisterObj, int, string}>
     */
    public static function xorWithTwoRegistersDataProvider(): array
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
            [Simulator::LONG_MODE, 0x45, 0x66, Register::R8W, 9374, Register::R9W, 4527, "\xC8"],
        ];
    }

    /**
     * @dataProvider xorWithTwoRegistersDataProvider
     * @small
     *
     * @param RegisterObj $firstRegister
     * @param RegisterObj $secondRegister
     */
    public function testXor31WithTwoRegisters(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        array $firstRegister,
        int $firstRegValue,
        array $secondRegister,
        int $secondRegValue,
        string $instruction,
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->exactly(2))
                  ->method('readRegister')
                  ->willReturnCallback(function ($register) use (
                      $firstRegister,
                      $firstRegValue,
                      $secondRegister,
                      $secondRegValue
                  ): int {
                      if ($register === $firstRegister) {
                          return $firstRegValue;
                      } elseif ($register === $secondRegister) {
                          return $secondRegValue;
                      }

                      $message = sprintf(
                          'Request made for incorrect register "%s".',
                          $register['name'],
                      );

                      $this->fail($message);
                  });

        $expected = $firstRegValue ^ $secondRegValue;
        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($firstRegister, $expected);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstruction')
                  ->willReturn($instruction)
                  ->with(1);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand31();
    }

    /**
     * @dataProvider xorWithTwoRegistersDataProvider
     * @small
     *
     * @param RegisterObj $firstRegister
     * @param RegisterObj $secondRegister
     */
    public function testXor33WithTwoRegisters(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        array $firstRegister,
        int $firstRegValue,
        array $secondRegister,
        int $secondRegValue,
        string $instruction,
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($requested) use ($prefixValue) {
                      return $requested === $prefixValue;
                  });

        $simulator->expects($this->exactly(2))
                  ->method('readRegister')
                  ->willReturnCallback(function ($register) use (
                      $firstRegister,
                      $firstRegValue,
                      $secondRegister,
                      $secondRegValue
                  ): int {
                      if ($register === $firstRegister) {
                          return $firstRegValue;
                      } elseif ($register === $secondRegister) {
                          return $secondRegValue;
                      }

                      $message = sprintf(
                          'Request made for incorrect register "%s".',
                          $register['name'],
                      );

                      $this->fail($message);
                  });

        $expected = $secondRegValue ^ $firstRegValue;
        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($secondRegister, $expected);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstruction')
                  ->willReturn($instruction)
                  ->with(1);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand33();
    }

    /**
     * @small
     */
    public function testXorClearsFlagsAfterExecution(): void
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
                  ->willReturn("\xC9")
                  ->with(1);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $xor->executeOperand31();
    }

    /**
     * @small
     */
    public function testXorWithIncorrectModByteFails(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // REX.RB xor r9d,r9d, (mod 2 - incorrect)
        // 0x45 0x31 0x89 (mod 2 - incorrect)
        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->expects($this->once())
                  ->method('getCodeAtInstruction')
                  ->willReturn("\x89")
                  ->with(1);

        $xor = new ExclusiveOr();
        $xor->setSimulator($simulator);

        $this->expectException(\RuntimeException::class);
        $xor->executeOperand31();
    }
}
