<?php

namespace shanept\AssemblySimulatorTests\Integration;

use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\SimulatorFactory;
use shanept\AssemblySimulatorTests\Fakes\ClosureMock;
use shanept\AssemblySimulatorTests\Fakes\TestFactoryInstructionOne;
use shanept\AssemblySimulatorTests\Fakes\TestFactoryInstructionTwo;

/**
 * @covers shanept\AssemblySimulator\SimulatorFactory
 */
class SimulatorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @depends shanept\AssemblySimulatorTests\Unit\SimulatorFactoryTest::testFactoryReturnsSimulatorInstance
     * @covers shanept\AssemblySimulator\SimulatorFactory
     */
    public function testFactoryRegistersAdditionalInstructionsInOrder(): void
    {
        /**
         * We will register two *custom* factory instructions, 'TestFactoryInstructionOne'
         * and 'TestFactoryInstructionTwo'. The first will be registered first,
         * thus will take priority over the second.
         */

        $cbOne = $this->createMock(ClosureMock::class);
        $cbTwo = $this->createMock(ClosureMock::class);

        // Now we set up two identical mocked instructions, then register them.
        TestFactoryInstructionOne::$opcode = 0xE8;
        TestFactoryInstructionTwo::$opcode = 0xE8;

        TestFactoryInstructionOne::$callback = $cbOne;
        TestFactoryInstructionTwo::$callback = $cbTwo;

        $simulator = SimulatorFactory::createSimulator(Simulator::PROTECTED_MODE, [
            TestFactoryInstructionOne::class,
            TestFactoryInstructionTwo::class,
        ]);

        $cbOne->expects($this->once())
              ->method('__invoke')
              ->willReturnCallback(function () use (&$simulator): bool {
                  $simulator->advanceInstructionPointer(1);
                  return true;
              });

        $cbTwo->expects($this->never())
              ->method('__invoke');

        // And see if we can call it.
        $simulator->setCodeBuffer("\xE8");
        $simulator->simulate();
    }
}
