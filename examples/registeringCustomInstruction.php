<?php
/**
 * This file provides an example for how one may register a custom instruction
 * with the simulator. For information on how the custom instruction is
 * implemented, see the "myCustomInstruction.php" example.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

include __DIR__ . "/../vendor/autoload.php";
include __DIR__ . "/myCustomInstruction.php";

use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Stack\StrictStack;

$simulator = new Simulator(Simulator::LONG_MODE, [
    'stack' => new StrictStack(),
]);

// Instantiate and register our custom instruction.
$instruction = new myCustomInstruction();
$instruction->setSimulator($simulator);

// My custom instruction will push 0x42 onto the stack.
$simulator->setCodeBuffer("\x01\x42");
$simulator->simulate();

$stack = $simulator->getStack();

/**
 * The stack now looks like: "\x42".
 */
