<?php

namespace shanept\AssemblySimulatorTests\Integration;

use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\SimulatorFactory;
use shanept\AssemblySimulatorTests\Fakes\TestAssemblyInstruction;
use shanept\AssemblySimulatorTests\Fakes\TestFactoryInstruction;

/**
 * @covers shanept\AssemblySimulator\SimulatorFactory
 */
class SimulatorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @depends shanept\AssemblySimulatorTests\Unit\SimulatorFactoryTest::testFactoryReturnsSimulatorInstance
     * @covers shanept\AssemblySimulator\SimulatorFactory
     */
    public function testFactoryRegistersAdditionalInstructions()
    {
        $simulator = null;

        // We will use the TestAssemblyInstruction class to create a mocked
        // callback before assigning it to the TestFactoryInstruction.
        $mockedInstruction = $this->createMock(TestAssemblyInstruction::class);
        $mockedInstruction->expects($this->once())
                          ->method('mockableCallback')
                          ->willReturnCallback(function() use (&$simulator) {
                              $simulator->advanceInstructionPointer(1);
                              return true;
                          });

        // Now we set our mocked instruction. We will intentionally overwrite
        // opcode E8 (CALL NEAR) to test we have priority over default instructions.
        TestFactoryInstruction::$opcode = 0xE8;
        TestFactoryInstruction::$callback = [&$mockedInstruction, 'mockableCallback'];

        $simulator = SimulatorFactory::createSimulator(Simulator::PROTECTED_MODE, [
            TestFactoryInstruction::class,
        ]);

        // And see if we can call it.
        $simulator->setCodeBuffer("\xE8");
        $simulator->simulate();
    }
}
