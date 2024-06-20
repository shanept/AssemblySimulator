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
}
