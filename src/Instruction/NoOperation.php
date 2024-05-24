<?php
/**
 * This defines the implementation for the No Operation instruction.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

/**
 * Implements the No Operation instruction.
 *
 * @see https://www.felixcloutier.com/x86/nop
 * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/NOP.htm
 *
 * @author Shane Thompson
 */
class NoOperation extends AssemblyInstruction
{
    public function register(): array
    {
        return [
            0x90 => [&$this, 'executeOperand90'],
            0x0F1F => [&$this, 'executeOperand0F1F'],
        ];
    }

    public function executeOperand90(): bool
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        return true;
    }

    public function executeOperand0F1F(): bool
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        // First byte is a ModRM.
        $byte = $this->parseModRmByte($sim->getCodeAtInstruction(1));
        $sim->advanceInstructionPointer(1);

        // If we don't have to parse an address, we are done.
        if (0b11 === $byte['mod']) {
            return true;
        }

        $address = $this->parseAddress($byte);
        $sim->advanceInstructionPointer($address->getDisplacement());

        return true;
    }
}
