<?php
/**
 * This defines the implementation for the Move instruction.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;

/**
 * Implements the Move instruction.
 *
 * @see https://www.felixcloutier.com/x86/mov
 * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/MOV.htm
 *
 * @author Shane Thompson
 */
class Move extends AssemblyInstruction
{
    public function register()
    {
        return [
            0x89 => [&$this, 'executeOperand89'],
            0x8B => [&$this, 'executeOperand8B'],
            0xB8 => [&$this, 'executeOperandBx'],
            0xB9 => [&$this, 'executeOperandBx'],
            0xBA => [&$this, 'executeOperandBx'],
            0xBB => [&$this, 'executeOperandBx'],
            0xBC => [&$this, 'executeOperandBx'],
            0xBD => [&$this, 'executeOperandBx'],
            0xBE => [&$this, 'executeOperandBx'],
            0xBF => [&$this, 'executeOperandBx'],
        ];
    }

    /**
     * Performs a MOV for a non-8-bit memory address.
     *
     * Implements MOV reg,imm for the opcode range \xB8-\xBF.
     *
     * @see https://www.felixcloutier.com/x86/mov
     * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/MOV.htm
     *
     * @return void
     */
    public function executeOperandBx()
    {
        $sim = $this->getSimulator();

        // Get the last bit of the operand.
        $opcode = ord($sim->getCodeAtInstruction(1)) & 0x7;
        $sim->advanceInstructionPointer(1);

        $opSize = $this->getOperandSize();
        $value = $sim->getCodeAtInstruction($opSize / 8);
        $value = $this->unpackImmediate($value, $opSize);

        $sim->advanceInstructionPointer($opSize / 8);

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_B);

        $register = Register::getByCode($opcode, $opSize, $rexSet, $regExt);

        $sim->writeRegister($register, $value);

        return true;
    }

    /**
     * Performs a MOV for a non-8-bit registry to ModRM byte.
     *
     * Implements MOV r/m,reg for the opcode range \x89.
     * This is the alternate encoding of MOV \x8b.
     *
     * @see https://www.felixcloutier.com/x86/mov
     * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/MOV.htm
     *
     * @return void
     */
    public function executeOperand89()
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $byte = $this->parseModRmByte($sim->getCodeAtInstruction(1));
        $sim->advanceInstructionPointer(1);

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_R);
        $rmExt = (bool) ($rex & Simulator::REX_B);

        $opSize = $this->getOperandSize();
        $rm = Register::getByCode($byte["rm"], $opSize, $rexSet, $rmExt);

        $reg = Register::getByCode($byte["reg"], $opSize, $rexSet, $regExt);
        $value = $sim->readRegister($reg);

        $sim->writeRegister($rm, $value);

        return true;
    }

    /**
     * Performs a MOV for a non-8-bit registry to ModRM byte.
     *
     * Implements MOV reg,r/m for the opcode range \x8b.
     *
     * @see https://www.felixcloutier.com/x86/mov
     * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/MOV.htm
     *
     * @return void
     */
    public function executeOperand8b()
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

        /**
         * Depending on the value of the mod bit, we may be moving from a
         * register or a memory address. If our ModRM mod field is 0b11, we
         * source our value from the register. Otherwise, we load it from a
         * memory address.
         */
        if (0b11 === $byte["mod"]) {
            $rmExt = (bool) ($rex & Simulator::REX_B);
            $rm = Register::getByCode($byte["rm"], $opSize, $rexSet, $rmExt);
            $value = $sim->readRegister($rm);
        } else {
            $address = $this->parseAddress($byte);
            $sim->advanceInstructionPointer($address->getDisplacement());
            $value = $address->getAddress();
        }

        $sim->writeRegister($reg, $value);

        return true;
    }
}
