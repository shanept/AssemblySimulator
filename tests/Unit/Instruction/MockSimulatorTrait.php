<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Simulator;

/**
 * NOTE: This trait must be inherited by a PHPUnit TestCase.
 */
trait MockSimulatorTrait
{
    /**
     * @return mixed
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
     * @param mixed $simulator
     * @param array<int, int> $initialRegisters
     *
     * @return mixed
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
                  ->will($this->returnCallback($readRegisterCb));

        $simulator->method('writeRegister')
                  ->will($this->returnCallback($writeRegisterCb));

        return $simulator;
    }
}
