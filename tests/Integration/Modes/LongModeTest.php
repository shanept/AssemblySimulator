<?php

namespace shanept\AssemblySimulatorTests\Integration\Modes;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\ExclusiveOr;

/**
 * @covers shanept\AssemblySimulator\Simulator
 */
class LongModeTest extends \PHPUnit\Framework\TestCase
{
    public function testOp66Uses16bitRegister(): void
    {
        $simulator = new Simulator(Simulator::LONG_MODE);
        $operation = new ExclusiveOr();
        $operation->setSimulator($simulator);

        $simulator->writeRegister(Register::RAX, PHP_INT_MAX);

        $simulator->setCodeBuffer("\x66\x31\xc0");
        $simulator->simulate();

        $actual = $simulator->readRegister(Register::RAX);
        $this->assertEquals(PHP_INT_MAX - 65535, $actual);
    }
}
