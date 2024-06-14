<?php
/**
 * This defines the implementation for the LoadEffectiveAddress instruction.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;

/**
 * Implements the LoadEffectiveAddress instruction.
 *
 * @see https://www.felixcloutier.com/x86/lea
 * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/LEA.htm
 *
 * @author Shane Thompson
 */
class LoadEffectiveAddress extends AssemblyInstruction
{
    public function register(): array
    {
        return [
            0x8D => [&$this, 'executeOperand8d'],
        ];
    }

    /**
     * Performs a Load Effective Address operation.
     *
     * Implements LEA reg,address for the LEA opcode \x8D.
     */
    public function executeOperand8d(): bool
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $byte = $this->parseModRmByte($sim->getCodeAtInstruction(1));
        $sim->advanceInstructionPointer(1);

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_R);

        $operandSize = $this->getOperandSize();
        $addressSize = $sim->getLargestInstructionWidth();
        $reg = Register::getByCode($byte["reg"], $operandSize, $rexSet, $regExt);

        if (0b11 === $byte["mod"]) {
            $message = sprintf(
                "LEA expected modrm mod byte to be a memory operand, " .
                    "register operand 0x%x received instead.",
                $byte["mod"],
            );

            throw new \RuntimeException($message);
        }

        $effectiveAddress = $this->parseAddress($byte);
        $address = $effectiveAddress->getAddress();

        /**
         * If we have an operand on a machine where the operand size is smaller
         * than the address size, we should mask the address down to the operand
         * size.
         */
        if ($operandSize < $addressSize) {
            $address &= $reg['mask'];
        }

        $sim->advanceInstructionPointer($effectiveAddress->getDisplacement());

        $sim->writeRegister($reg, $address);

        return true;
    }
}
