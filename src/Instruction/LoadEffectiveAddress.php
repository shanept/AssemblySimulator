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
    public function register()
    {
        return [
            0x8D => [&$this, 'executeOperand8d'],
        ];
    }

    /**
     * Performs a Load Effective Address operation.
     *
     * Implements LEA reg,address for the LEA opcode \x8D.
     *
     * @see https://www.felixcloutier.com/x86/lea
     * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/LEA.htm
     *
     * @return void
     */
    public function executeOperand8d()
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $byte = $this->parseModRmByte($sim->getCodeAtInstruction(1));
        $sim->advanceInstructionPointer(1);

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_R);

        $opSize = $this->getOperandSize();
        $reg = Register::getByCode($byte["reg"], $opSize, $rexSet, $regExt);

        if (0b11 === $byte["mod"]) {
            throw new \RuntimeException(
                sprintf(
                    "LEA expected modrm mod byte to be a memory operand, " .
                        "register operand 0x%x received instead.",
                    $byte["mod"]
                )
            );
        }

        $effectiveAddress = $this->parseAddress($byte);
        $address = $effectiveAddress->getAddress() + $sim->getAddressBase();

        $sim->advanceInstructionPointer($effectiveAddress->getDisplacement());

        $sim->writeRegister($reg, $address);

        return true;
    }
}
