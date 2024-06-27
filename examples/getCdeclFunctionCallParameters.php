<?php
/**
 * This file provides an example of how one may extract the parameters out of a
 * cdecl function call. The assembly code in use is from the PHP v8.3.7 x86
 * thread safe "php8ts.dll" file, specifically prior to the call to
 * zend_register_string_constant to register the PHP_VERSION constant with the
 * string value of "8.3.7".
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

include __DIR__ . "/../vendor/autoload.php";

use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Instruction\{
    ExclusiveOr,
    LoadEffectiveAddress,
    Move,
    Pop,
    Push
};
use shanept\AssemblySimulator\Stack\StrictStack;

/**
 * Unless we want the simulator to use a black hole stack (NullStack), we will
 * have to pass a specific implemenation to the constructor.
 *
 * Possible options are:
 *   - StrictStack: Fully operational stack. Throws exceptions on invalid stack operations.
 *   - ForgivingStack: Fully operational stack. Fails silently.
 *   - NullStack: Black hole stack, nothing in, NUL bytes out.
 */
$stack = new StrictStack();

// Our file is x86, so we will use PROTECTED_MODE.
$simulator = new Simulator(Simulator::PROTECTED_MODE, [
    'stack' => $stack,
]);

/**
 * Instantiate our instruction set.
 *
 * NOTE: This is the verbose way of doing it, and is purely for instructional
 * purposes. We recommend you use the provided simulator factory to get an
 * instance with these instructions already loaded.
 *
 * You should only use this method to register your own instructions, or if you
 * do not wish to use the default instruction set.
 */
$exclusiveOr = new ExclusiveOr();
$lea = new LoadEffectiveAddress();
$mov = new Move();
$pop = new Pop();
$push = new Push();

/**
 * Link our instruction set with the simulator. This is typically handled by the
 * simulator factory for the default instruction set.
 */
$exclusiveOr->setSimulator($simulator);
$lea->setSimulator($simulator);
$mov->setSimulator($simulator);
$pop->setSimulator($simulator);
$push->setSimulator($simulator);

$assemblyCode =
    "\x56" .                                        // push esi
    "\x68\x18\xA7\x60\x10" .                        // push 0x1060A718
    "\x6A\x0B" .                                    // push 0xB
    "\x68\x98\x4D\x61\x10";                         // push 0x10614D98

// Let's add something to the ESI register so we have a value to push to the stack.
$simulator->writeRegister(Register::ESI, 0x42);

// Simulate our assembly code.
$simulator->setCodeBuffer($assemblyCode);
$simulator->simulate();

$stack = $simulator->getStack();

/**
 * The stack now looks like:
 *
 * Index 1 - push esi (We set ESI with 0x42 on line 59)
 * Index 2 - push 0x1060A718 (Integer pointing to memory address)
 * Index 3 - push 0xB
 * Index 4 - push 0x10614D98 (Integer pointing to memory address)
 *
 * array() {
 *     1 => 0x42,
 *     2 => 0x1060A718,
 *     3 => 0xB,
 *     4 => 0x10614D98
 * }
 *
 * NOTE: Index 1 refers to parameter 4 of the function call, and Index 4 refers
 * to Parameter 1 of the function call, as the x86 stack works on the last-on,
 * first-off principle.
 */
