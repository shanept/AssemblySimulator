<?php
/**
 * This defines the implementation for the Push instruction.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;

/**
 * Implements the Push instruction.
 *
 * @see https://www.felixcloutier.com/x86/push
 * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/PUSH.htm
 *
 * @author Shane Thompson
 */
class Push extends AssemblyInstruction
{
    /**
     * {@inheritDoc}
     */
    public function register(): array
    {
        return [
            0x50 => [&$this, 'executeOperand5x'],
            0x51 => [&$this, 'executeOperand5x'],
            0x52 => [&$this, 'executeOperand5x'],
            0x53 => [&$this, 'executeOperand5x'],
            0x54 => [&$this, 'executeOperand5x'],
            0x55 => [&$this, 'executeOperand5x'],
            0x56 => [&$this, 'executeOperand5x'],
            0x57 => [&$this, 'executeOperand5x'],
            0x68 => [&$this, 'executeOperand68'],
            0x6A => [&$this, 'executeOperand6a'],
        ];
    }

    /**
     * Performs a stack push operation.
     *
     * Implements PUSH register for the push opcode range 0x50 - 0x57.
     */
    public function executeOperand5x(): bool
    {
        $sim = $this->getSimulator();

        $opcode = ord($sim->getCodeAtInstructionPointer(1)) & 0x7;
        $sim->advanceInstructionPointer(1);

        $opSize = $this->getOperandSize();
        $stackPointer = $this->getStackPointerRegister();

        // Incrememnt stack pointer
        $stackPosition = $sim->readRegister($stackPointer) - ($opSize / 8);
        $sim->writeRegister($stackPointer, $stackPosition);

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_B);

        $register = Register::getByCode($opcode, $opSize, $rexSet, $regExt);

        $value = $sim->readRegister($register);

        $value = $this->packImmediate($value, $opSize);
        $sim->writeStackAt($stackPosition, $value);

        return true;
    }

    /**
     * Performs a push operation.
     *
     * Performs PUSH imm16/32/64 for opcode 0x68.
     */
    public function executeOperand68(): bool
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $opSize = $this->getOperandSize();

        // Operand 68 is limited to 32-bit displacement max.
        $dispSize = min(4, $opSize / 8);
        $value = $sim->getCodeAtInstructionPointer($dispSize);

        $sim->advanceInstructionPointer($dispSize);

        $stackPointer = $this->getStackPointerRegister();
        $stackPosition = $sim->readRegister($stackPointer) - $dispSize;

        $sim->writeRegister($stackPointer, $stackPosition);
        $sim->writeStackAt($stackPosition, $value);

        return true;
    }

    /**
     * Performs a push operation.
     *
     * Performs PUSH imm8 for opcode 0x6a.
     */
    public function executeOperand6a(): bool
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $value = $sim->getCodeAtInstructionPointer(1);
        $sim->advanceInstructionPointer(1);

        $opSize = Simulator::TYPE_BYTE;
        $stackPointer = $this->getStackPointerRegister();
        $stackPosition = $sim->readRegister($stackPointer) - 1;

        $sim->writeRegister($stackPointer, $stackPosition);
        $sim->writeStackAt($stackPosition, $value);

        return true;
    }
}
