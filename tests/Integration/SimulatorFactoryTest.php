<?php

namespace shanept\AssemblySimulatorTests\Integration;

use shanept\AssemblySimulator\SimulatorFactory;

class SimulatorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers shanept\AssemblySimulator\SimulatorFactory
     */
    public function testFactoryRegistersInstructionsWithSimulator(): void
    {
        $simulator = SimulatorFactory::createSimulator();

        // Extract the registered instructions from the instantiated simulator.
        // Convert to a list of class names, for comparison with our defaults.
        $reflectionInstructions = new \ReflectionProperty($simulator, 'registeredInstructions');
        $registered = $reflectionInstructions->getValue($simulator);
        $registered = array_column($registered, 'reference');
        $registered = array_map('get_class', $registered);

        $expected = SimulatorFactory::getDefaultInstructionSet();

        $this->assertEqualsCanonicalizing($expected, $registered);
    }
}
