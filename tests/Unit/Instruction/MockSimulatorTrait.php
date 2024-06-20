<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Simulator;

/**
 * NOTE: This trait must be inherited by a PHPUnit TestCase.
 */
trait MockSimulatorTrait
{
    /**
     * @return MockSimulator
     */
    public function getMockSimulator(int $mode = Simulator::REAL_MODE)
    {
        $mock = $this->createMock(Simulator::class);

        $mock->method('getMode')
             ->willReturn($mode);

        $mock->method('getLargestInstructionWidth')
             ->willReturn(8 << $mode);

        return $mock;
    }

    /**
     * @deprecated
     *
     * @param MockSimulator $simulator
     * @param array<int, int> $initialRegisters
     *
     * @return MockSimulator
     */
    public function mockSimulatorRegisters($simulator, array $initialRegisters = [])
    {
        static $registers;

        $registers = $initialRegisters;

        $readRegisterCb = function ($reg) use (&$registers) {
            return $registers[$reg['offset']];
        };

        $writeRegisterCb = function ($reg, $value) use (&$registers) {
            $registers[$reg['offset']] = $value;
        };

        $simulator->method('readRegister')
                  ->willReturnCallback($readRegisterCb);

        $simulator->method('writeRegister')
                  ->willReturnCallback($writeRegisterCb);

        return $simulator;
    }
}
