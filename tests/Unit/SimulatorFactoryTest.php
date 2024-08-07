<?php

namespace shanept\AssemblySimulatorTests\Unit;

use PHPUnit\Framework\TestCase;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\SimulatorFactory;

/**
 * @covers shanept\AssemblySimulator\SimulatorFactory
 */
class SimulatorFactoryTest extends TestCase
{
    /**
     * @small
     */
    public function testFactoryDefaultInstructionsIncludeAllClasses(): void
    {
        // Get the list of all classes from the filesystem.
        $instructionDir = realpath(__DIR__ . '/../../src/Instruction');
        $files = glob($instructionDir . '/*.php');

        if (! $files) {
            $this->fail('Could not list Instruction directory.');
        }

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
     * @small
     */
    public function testFactoryReturnsSimulatorInstance(): void
    {
        $simulator = SimulatorFactory::createSimulator(Simulator::LONG_MODE);

        $this->assertInstanceOf(Simulator::class, $simulator);
        $this->assertEquals(Simulator::LONG_MODE, $simulator->getMode());
    }
}
