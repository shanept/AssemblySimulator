<?php
/**
 * This defines the implementation for the Pop instruction.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;

/**
 * Implements the Pop instruction.
 *
 * @see https://www.felixcloutier.com/x86/pop
 * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/POP.htm
 *
 * @author Shane Thompson
 */
class Pop extends AssemblyInstruction
{
    public function register(): array
    {
        return [
            0x58 => [&$this, 'executeOperand5x'],
            0x59 => [&$this, 'executeOperand5x'],
            0x5A => [&$this, 'executeOperand5x'],
            0x5B => [&$this, 'executeOperand5x'],
            0x5C => [&$this, 'executeOperand5x'],
            0x5D => [&$this, 'executeOperand5x'],
            0x5E => [&$this, 'executeOperand5x'],
            0x5F => [&$this, 'executeOperand5x'],
        ];
    }

    /**
     * Performs a stack pop operation.
     *
     * Implements POP register for the pop opcode range 0x58 - 0x5F.
     */
    public function executeOperand5x(): bool
    {
        $sim = $this->getSimulator();

        $opcode = ord($sim->getCodeAtInstruction(1)) & 0x7;
        $sim->advanceInstructionPointer(1);

        $opSize = $this->getOperandSize();
        $stackPointer = $this->getStackPointerRegister($sim->getMode());
        $stackPosition = $sim->readRegister($stackPointer);

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_B);

        $register = Register::getByCode($opcode, $opSize, $rexSet, $regExt);

        $value = $sim->readStackAt($stackPosition);
        $sim->clearStackAt($stackPosition);

        $sim->writeRegister($stackPointer, $stackPosition - 1);
        $sim->writeRegister($register, $value, $opSize);

        return true;
    }

    /**
     * Returns the correct stack pointer for the Simulator mode.
     */
    protected function getStackPointerRegister($mode)
    {
        $pointers = [
            null,
            Register::SP,
            Register::ESP,
            Register::RSP,
        ];

        return $pointers[$mode];
    }
}
