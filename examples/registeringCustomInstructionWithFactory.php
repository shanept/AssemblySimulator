<?php
/**
 * This file provides equivalent functionality to the example provided in
 * "regiseringCustomInstructions.php", however we show how the simulator may
 * be created through the factory, and the custom instruction is fed to the
 * factory as well.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

include __DIR__ . "/../vendor/autoload.php";
include __DIR__ . "/myCustomInstruction.php";

use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\SimulatorFactory;

/**
 * Creates the simulator with the default instruction set and registers the
 * myCustomInstruction instruction processor.
 */
$simulator = SimulatorFactory::createSimulator(Simulator::LONG_MODE, [
    myCustomInstruction::class
]);

// My custom instruction will push 0x42 onto the stack.
$simulator->setCodeBuffer("\x01\x42");
$simulator->simulate();

$stack = $simulator->getStack();

/**
 * The stack now looks like:
 *
 * Index 1 - The value we told the instruction to push onto the stack.
 *
 * array() {
 *     0 => 0x42
 * }
 */
