<?php

namespace shanept\AssemblySimulatorTests\Unit;

use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\SimulatorFactory;
use shanept\AssemblySimulatorTests\Fakes\TestAssemblyInstruction;

class SimulatorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers shanept\AssemblySimulator\SimulatorFactory
     */
    public function testFactoryDefaultInstructionsIncludeAllClasses()
    {
        // Get the list of all classes from the filesystem.
        $instructionDir = realpath(__DIR__ . '/../../src/Instruction');
        $files = glob($instructionDir . '/*.php');

        // Remove AssemblyInstruction
        $files = array_filter($files, function ($filename) {
            return false === strpos($filename, 'AssemblyInstruction.php');
        });

        $fqcnFormat = 'shanept\AssemblySimulator\Instruction\%s';

        $expected = array_map(function ($filepath) use ($fqcnFormat) {
            // Remove filesystem artifacts
            $filepath = basename($filepath);
            $filepath = explode('.', $filepath)[0];
            $filepath = ltrim($filepath, DIRECTORY_SEPARATOR);

            // Transform into fully qualified class name.
            $classname = sprintf($fqcnFormat, $filepath);

            return $classname;
        }, $files);

        $defaultInstructionSet = SimulatorFactory::getDefaultInstructionSet();

        $this->assertEqualsCanonicalizing($expected, $defaultInstructionSet);
    }

    /**
     * @covers shanept\AssemblySimulator\SimulatorFactory
     */
    public function testFactoryReturnsSimulatorInstance()
    {
        $simulator = SimulatorFactory::createSimulator();

        $this->assertInstanceOf(Simulator::class, $simulator);
    }

    /**
     * @covers shanept\AssemblySimulator\SimulatorFactory
     */
    public function testFactoryRegistersCustomInstructionWithSimulator()
    {
        $simulator = SimulatorFactory::createSimulator(Simulator::LONG_MODE, [
            TestAssemblyInstruction::class,
        ]);

        // Extract the registered instructions from the instantiated simulator.
        // Convert to a list of class names, for comparison with our defaults.
        $reflectionInstructions = new \ReflectionProperty($simulator, 'registeredInstructions');
        $registered = $reflectionInstructions->getValue($simulator);
        $registered = array_column($registered, 'reference');
        $registered = array_map('get_class', $registered);

        $this->assertContains(TestAssemblyInstruction::class, $registered);
    }
}
