# AssemblySimulator

The AssemblySimulator project provides a means to step through compiled assembly code, operation by operation, and provides access to registers and the stack for easy querying of values. The implementation is quite simplistic, we only implement a few basic instructions. However the architecture allows for easy extensibility of the instruction set, to satisfy your project's needs.

## The Simulator

The simulator may be manually instantiated, or instantiated by the SimulatorFactory. Note that using the SimulatorFactory does not restrict you from registering further instructions after the simulator is created. The following are equivalent:

```php
<?php
// METHOD 1: Using the factory.
$simulator = SimulatorFactory::createSimulator(Simulator::PROTECTED_MODE);

// METHOD 2: Manually creating the simulator.
$simulator = new Simulator(Simulator::PROTECTED_MODE);

// Instantiate default instruction set manually.
$xor = new ExclusiveOr();
$lea = new LoadEffectiveAddress();
$mov = new Move();
$pop = new Pop();
$push = new Push();

// Link instruction set to simulator.
$xor->setSimulator($simulator);
$lea->setSimulator($simulator);
$mov->setSimulator($simulator);
$pop->setSimulator($simulator);
$push->setSimulator($simulator);
```

## Modes

The simulator supports operating in real, protected and long mode. In order to specify the mode under which the simulator should operate, provide one of the mode constants to either the constructor or the setMode function:

 - Simulator::REAL_MODE
 - Simulator::PROTECTED_MODE
 - Simulator::LONG_MODE

Note: If you set the simulator mode with $simulator->setMode() function, you must perform a reset before performing any simulations, with a call to $simulator->reset(). See the Simulator Reset section.

## Resetting the Simulator

There are some circumstances where you will need to reset the simulator - think of it like hitting the reset switch on your computer. The reset function is used to restore the simulator to a better known state. This includes clearing registers and the stack.

## Registering Custom Instructions

In order to use the simulator, it must be instantiated with the instruction set you wish to use. An example is provided here with the default instruction set. Please note, if you wish to use the simulator with the default instruction set, you can simply use the SimulatorFactory.

For more information, see the custom instruction example.

```php
<?php
$simulator = new Simulator(Simulator::PROTECTED_MODE);

// Instantiate our instruction set.
$exclusiveOr = new ExclusiveOr;
$lea = new LoadEffectiveAddress;
$mov = new Move;
$pop = new Pop;
$push = new Push;

// Link our instruction set with the simulator.
$exclusiveOr->setSimulator($simulator);
$lea->setSimulator($simulator);
$mov->setSimulator($simulator);
$pop->setSimulator($simulator);
$push->setSimulator($simulator);
```

## The Code Buffer

Prior to simulation, the simulator must be provided a code buffer off which to operate. This must be provided as a binary string.

```php
<?php
$simulator = new Simulator(Simulator::PROTECTED_MODE);

// Taken from PHP v8.3.7 Thread-Safe Windows "php8ts.dll" binary.
$assemblyCode =
    "\x56" .                                        // push esi
    "\x68\x18\xA7\x60\x10" .                        // push 0x1060A718
    "\x6A\x0B" .                                    // push 0xB
    "\x68\x98\x4D\x61\x10";                         // push 0x10614D98

$simulator->setCodeBuffer($assemblyCode);

// OR: Reads the same string from the php8ts.dll file.
$fp = fopen("php8ts.dll", "rb");
fseek($fp, 4782677);
$assemblyCode = fread($fp, 13);
fclose($fp);

$simulator->setCodeBuffer($assemblyCode);

// The simulator can now run.
$simulator->simulate();
```

## Examples
For more information, see the examples under the example directory.

- examples/getCdeclFunctionCallParameters.php - For a basic example of how to use the simulator.
- examples/registeringCustomInstruction.php - For an example of how to build and use a custom instruction.
- examples/registeringCustomInstructionWithFactory.php - As above, uses the SimulatorFactory.
- examples/myCustomInstruction.php - For an example of how a custom instruction is implemented.
