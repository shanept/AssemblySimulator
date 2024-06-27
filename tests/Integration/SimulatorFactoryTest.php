<?php

namespace shanept\AssemblySimulatorTests\Integration;

use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\SimulatorFactory;
use shanept\AssemblySimulator\Stack\Stack;
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
        // We will assert that the stack class is passed to the simulator by
        // ensuring the setAddress method is called from the simulator constructor.
        $mockStack = $this->createMock(Stack::class);
        $mockStack->expects($this->once())
                  ->method('setAddress');

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
            'stack' => $mockStack,
        ], [
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
