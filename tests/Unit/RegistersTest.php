<?php

namespace shanept\AssemblySimulatorTests\Unit;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;

class RegistersTest extends \PHPUnit\Framework\TestCase
{
    public function testEnsureSimulatorSetsUp8RegistersInRealMode(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $registers = $simulator->getRawRegisters();

        $this->assertEquals(8, count($registers));
    }

    public function testEnsureSimulatorSetsUp8RegistersInProtectedMode(): void
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $registers = $simulator->getRawRegisters();

        $this->assertEquals(8, count($registers));
    }

    public function testEnsureSimulatorSetsUp16RegistersInLongMode(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $registers = $simulator->getRawRegisters();

        $this->assertEquals(16, count($registers));
    }

    /**
     * @return array<int, array{RegisterObj, int}>
     */
    public static function writeRegisterDataProvider(): array
    {
        return [
            [Register::AX, 135],
            [Register::AX, 30],
            [Register::AX, 0],
            [Register::BX, 139],
            [Register::BX, 39],
            [Register::BX, 0],
            [Register::CX, 125],
            [Register::CX, 25],
            [Register::CX, 0],
            [Register::DX, 100],
            [Register::DX, 50],
            [Register::DX, 0],
        ];
    }

    /**
     * @dataProvider writeRegisterDataProvider
     *
     * @param RegisterObj $register
     */
    public function testWriteRegister(array $register, int $value): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $simulator->writeRegister($register, $value);
        $this->assertEquals($value, $simulator->readRegister($register));
    }

    /**
     * @depends testWriteRegister
     */
    public function testGetIndexedRegisters(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);

        $expected = [
            "%rax" => 0x0, "%rcx" => 0x1, "%rdx" => 0x2, "%rbx" => 0x3,
            "%rsp" => 0x4, "%rbp" => 0x5, "%rsi" => 0x6, "%rdi" => 0x7,
            "%r8" => 0x8,  "%r9" => 0x9,  "%r10" => 0xa, "%r11" => 0xb,
            "%r12" => 0xc, "%r13" => 0xd, "%r14" => 0xe, "%r15" => 0xf,
        ];

        // Write each register value to the simulator.
        foreach ($expected as $register => $value) {
            // transform %reg into REG
            $register = strtoupper(substr($register, 1));
            $reg = constant(Register::class . "::" . $register);

            $simulator->writeRegister($reg, $value);
        }

        $actual = $simulator->getIndexedRegisters();

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @return array<int, array{int, RegisterObj, int, RegisterObj, int, int}>
     */
    public static function settingDifferentWidthRegistersDataProvider(): array
    {
        return [
            // Set AX then zero AL in Real mode.
            [Simulator::REAL_MODE, Register::AX, 65535, Register::AL, 0, 65280],

            // Set EAX then zero AX in Protected mode.
            [Simulator::PROTECTED_MODE, Register::EAX, 4294967295, Register::AX, 0, 4294901760],

            // Set RAX then zero AX in Long mode.
            [Simulator::LONG_MODE, Register::RAX, PHP_INT_MAX, Register::AX, 0, PHP_INT_MAX - 65535],

            // Set RAX then EAX in Long mode. We should be left with the EAX
            // set value.
            [Simulator::LONG_MODE, Register::RAX, PHP_INT_MAX, Register::EAX, 4294967295, 4294967295],
        ];
    }

    /**
     * @depends testWriteRegister
     * @dataProvider settingDifferentWidthRegistersDataProvider
     *
     * @param RegisterObj $largerRegister
     * @param RegisterObj $smallerRegister
     */
    public function testSettingDifferentWidthRegisters(
        int $simulatorMode,
        array $largerRegister,
        int $largerRegisterValue,
        array $smallerRegister,
        int $smallerRegisterValue,
        int $expectedValue,
    ): void {
        $simulator = new Simulator($simulatorMode);

        $simulator->writeRegister($largerRegister, $largerRegisterValue);
        $simulator->writeRegister($smallerRegister, $smallerRegisterValue);

        $actual = $simulator->readRegister($largerRegister);
        $this->assertEquals($expectedValue, $actual);
    }

    /**
     * @depends testWriteRegister
     */
    public function testSettingEaxThrowsExceptionInRealMode(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $this->expectException(\LogicException::class);
        $simulator->writeRegister(Register::EAX, 0);
    }

    /**
     * @depends testWriteRegister
     */
    public function testSettingRaxThrowsExceptionInProtectedMode(): void
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $this->expectException(\LogicException::class);
        $simulator->writeRegister(Register::RAX, 0);
    }

    public function testRegisterGetCodeReturnsUniformByteRegisterIfOperandNotExtended(): void
    {
        // REX is set but not for this byte
        $register = Register::getByCode(
            Register::SPL['code'],
            Simulator::TYPE_BYTE,
            true,
            false,
        );

        $this->assertEquals('%spl', $register['name']);
    }

    public function testRegisterGetCodeReturnsExtendedRegisterIfOperandExtended(): void
    {
        // REX is set but not for this byte
        $register = Register::getByCode(
            Register::SPL['code'],
            Simulator::TYPE_BYTE,
            true,
            true,
        );

        $this->assertEquals('%r12b', $register['name']);
    }
}
