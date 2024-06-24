<?php

namespace shanept\AssemblySimulatorTests\Unit;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;

/**
 * @covers shanept\AssemblySimulator\Register
 * @covers shanept\AssemblySimulator\Simulator
 */
class RegistersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @small
     */
    public function testEnsureSimulatorSetsUp8RegistersInRealMode(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $registers = $simulator->getRawRegisters();

        $this->assertEquals(8, count($registers));
    }

    /**
     * @small
     */
    public function testEnsureSimulatorSetsUp8RegistersInProtectedMode(): void
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $registers = $simulator->getRawRegisters();

        $this->assertEquals(8, count($registers));
    }

    /**
     * @small
     */
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
     * @small
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
     * @small
     *
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

        $this->assertEquals($expected, $actual);
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

            // Set EAX then write AL in Protected mode.
            [Simulator::PROTECTED_MODE, Register::EAX, 4227858431, Register::AL, 42, 4227858218],

            // Set RAX then zero AX in Long mode.
            [Simulator::LONG_MODE, Register::RAX, PHP_INT_MAX, Register::AX, 0, PHP_INT_MAX - 65535],

            // Set RAX then EAX in Long mode. We should be left with the EAX
            // set value.
            [Simulator::LONG_MODE, Register::RAX, PHP_INT_MAX, Register::EAX, 4294967295, 4294967295],
        ];
    }

    /**
     * @dataProvider settingDifferentWidthRegistersDataProvider
     * @small
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
     * @return array<int, array{int, RegisterObj, int, RegisterObj, int}>
     */
    public static function readSmallerRegister(): array
    {
        return [
            [Simulator::REAL_MODE, Register::AX, 65535, Register::AL, 255],
            [Simulator::PROTECTED_MODE, Register::EAX, 4294967295, Register::AX, 65535],
            [Simulator::PROTECTED_MODE, Register::EAX, 4227858427, Register::AL, 251],
            [Simulator::LONG_MODE, Register::RAX, PHP_INT_MAX, Register::EAX, 4294967295],
            [Simulator::LONG_MODE, Register::R8, 4321, Register::R8B, 225],
        ];
    }

    /**
     * @dataProvider readSmallerRegister
     * @small
     *
     * @depends testWriteRegister
     *
     * @param RegisterObj $largerRegister
     * @param RegisterObj $smallerRegister
     */
    public function testReadSmallerRegister(
        int $simulatorMode,
        array $largerRegister,
        int $writeValue,
        array $smallerRegister,
        int $expected,
    ): void {
        $simulator = new Simulator($simulatorMode);

        $simulator->writeRegister($largerRegister, $writeValue);

        $readValue = $simulator->readRegister($smallerRegister);

        $this->assertEquals($expected, $readValue);
    }

    /**
     * @small
     *
     * @depends testWriteRegister
     */
    public function testSettingEaxThrowsExceptionInRealMode(): void
    {
        $simulator = new Simulator(Simulator::REAL_MODE);

        $this->expectException(\LogicException::class);
        $simulator->writeRegister(Register::EAX, 0);
    }

    /**
     * @small
     *
     * @depends testWriteRegister
     */
    public function testSettingRaxThrowsExceptionInProtectedMode(): void
    {
        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $this->expectException(\LogicException::class);
        $simulator->writeRegister(Register::RAX, 0);
    }

    /**
     * @return array<int, array{int, int, bool, bool, string}>
     */
    public static function registerGetCodeDataProvider(): array
    {
        $f = false;
        $t = true;

        return [
            [0, 8, $f, $f, '%al'], [0, 8, $t, $f, '%al'], [0, 16, $f, $f, '%ax'], [0, 32, $f, $f, '%eax'], [0, 64, $f, $f, '%rax'],
            [1, 8, $f, $f, '%cl'], [1, 8, $t, $f, '%cl'], [1, 16, $f, $f, '%cx'], [1, 32, $f, $f, '%ecx'], [1, 64, $f, $f, '%rcx'],
            [2, 8, $f, $f, '%dl'], [2, 8, $t, $f, '%dl'], [2, 16, $f, $f, '%dx'], [2, 32, $f, $f, '%edx'], [2, 64, $f, $f, '%rdx'],
            [3, 8, $f, $f, '%bl'], [3, 8, $t, $f, '%bl'], [3, 16, $f, $f, '%bx'], [3, 32, $f, $f, '%ebx'], [3, 64, $f, $f, '%rbx'],
            [4, 8, $f, $f, '%ah'], [4, 8, $t, $f, '%spl'], [4, 16, $f, $f, '%sp'], [4, 32, $f, $f, '%esp'], [4, 64, $f, $f, '%rsp'],
            [5, 8, $f, $f, '%ch'], [5, 8, $t, $f, '%bpl'], [5, 16, $f, $f, '%bp'], [5, 32, $f, $f, '%ebp'], [5, 64, $f, $f, '%rbp'],
            [6, 8, $f, $f, '%dh'], [6, 8, $t, $f, '%sil'], [6, 16, $f, $f, '%si'], [6, 32, $f, $f, '%esi'], [6, 64, $f, $f, '%rsi'],
            [7, 8, $f, $f, '%bh'], [7, 8, $t, $f, '%dil'], [7, 16, $f, $f, '%di'], [7, 32, $f, $f, '%edi'], [7, 64, $f, $f, '%rdi'],

            [0, 8, $t, $t, '%r8b'], [0, 16, $t, $t, '%r8w'], [0, 32, $t, $t, '%r8d'], [0, 64, $t, $t, '%r8'],
            [1, 8, $t, $t, '%r9b'], [1, 16, $t, $t, '%r9w'], [1, 32, $t, $t, '%r9d'], [1, 64, $t, $t, '%r9'],
            [2, 8, $t, $t, '%r10b'], [2, 16, $t, $t, '%r10w'], [2, 32, $t, $t, '%r10d'], [2, 64, $t, $t, '%r10'],
            [3, 8, $t, $t, '%r11b'], [3, 16, $t, $t, '%r11w'], [3, 32, $t, $t, '%r11d'], [3, 64, $t, $t, '%r11'],
            [4, 8, $t, $t, '%r12b'], [4, 16, $t, $t, '%r12w'], [4, 32, $t, $t, '%r12d'], [4, 64, $t, $t, '%r12'],
            [5, 8, $t, $t, '%r13b'], [5, 16, $t, $t, '%r13w'], [5, 32, $t, $t, '%r13d'], [5, 64, $t, $t, '%r13'],
            [6, 8, $t, $t, '%r14b'], [6, 16, $t, $t, '%r14w'], [6, 32, $t, $t, '%r14d'], [6, 64, $t, $t, '%r14'],
            [7, 8, $t, $t, '%r15b'], [7, 16, $t, $t, '%r15w'], [7, 32, $t, $t, '%r15d'], [7, 64, $t, $t, '%r15'],
        ];
    }

    /**
     * @dataProvider registerGetCodeDataProvider
     * @small
     */
    public function testRegisterGetCode(
        int $code,
        int $size,
        bool $rexPrefixSet,
        bool $rexExtendedRegisters,
        string $expected,
    ): void {
        $register = Register::getByCode(
            $code,
            $size,
            $rexPrefixSet,
            $rexExtendedRegisters,
        );

        $this->assertEquals($expected, $register['name']);
    }

    /**
     * @small
     */
    public function testRegisterGetCodeDefaultsToNonRex(): void
    {
        $register = Register::getByCode(
            Register::R12['code'],
            Simulator::TYPE_BYTE,
        );

        $this->assertEquals('%ah', $register['name']);
    }

    /**
     * @small
     */
    public function testRegisterGetCodeDefaultsToNonExtended(): void
    {
        $register = Register::getByCode(
            Register::R12['code'],
            Simulator::TYPE_QUAD,
            true,
        );

        $this->assertEquals('%rsp', $register['name']);
    }
}
