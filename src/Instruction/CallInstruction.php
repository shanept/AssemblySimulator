<?php
/**
 * This defines a processor for the x86 or x86_64 CALL instruction. This will
 * assist in the extraction of registered PHP constants.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

use shanept\AssemblySimulator\Simulator;

/**
 * Defines an x86 or x86_64 CALL processor to aid in the identification and
 * handling of PHP constant registrations.
 *
 * @see https://www.felixcloutier.com/x86/call
 * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/CALL.htm
 *
 * @author Shane Thompson
 */
class CallInstruction extends AssemblyInstruction
{
    private $handleAddressCb;

    /**
     * @param ?callable $onAddress The callback to call when we encounter an
     *                             address. This callback will receive the
     *                             parsed address as the first parameter.
     */
    public function __construct($onAddress = null)
    {
        if (! is_null($onAddress) && ! is_callable($onAddress)) {
            $message = sprintf(
                'Expected a callable or null, "%s" received.',
                var_export($onAddress, true),
            );

            throw new \LogicException($message);
        }

        $this->handleAddressCb = $onAddress;
    }

    public function register(): array
    {
        return [
            0xE8 => [&$this, 'executeOperandE8'],
        ];
    }

    /**
     * Performs a near call operation on an immediate value.
     *
     * Implements call rel32 for the xor opcode \xE8. rel16 not implemented as
     * it is not required here.
     *
     * This instruction parses the opcode information then sends it to a
     * callback function to do something with it.
     */
    public function executeOperandE8(): bool
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $address = $sim->getCodeAtInstruction(4);
        $sim->advanceInstructionPointer(4);
        $address = $this->unpackImmediate($address, Simulator::TYPE_DWRD);

        // If the most significant bit is set, we have a 2's complement.
        // We should calculate the signed negative integer from it.
        if (($address >> 31) & 1) {
            $address = -(($address ^ 0xFFFFFFFF) + 1);
        }

        $instructionPointer = $sim->getInstructionPointer();

        // Push our instruction pointer to the stack.
        $stackPointer = $this->getStackPointerRegister();
        $stackPosition = $sim->readRegister($stackPointer);
        $sim->writeStackAt($stackPosition, $instructionPointer);
        $sim->writeRegister($stackPointer, ++$stackPosition);

        // The address is relative. We should make it absolute.
        $addr = $sim->getAddressBase() + $instructionPointer + $address;

        if (is_callable($this->handleAddressCb)) {
            call_user_func($this->handleAddressCb, $addr);
        }

        return true;
    }
}
