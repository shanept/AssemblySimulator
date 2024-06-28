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
    /**
     * {@inheritDoc}
     */
    public function register(): array
    {
        return [
            0x88 => [&$this, 'executeOperand88'],
            0x89 => [&$this, 'executeOperand89'],
            0x8A => [&$this, 'executeOperand8A'],
            0x8B => [&$this, 'executeOperand8B'],
            0xB0 => [&$this, 'executeOperandBx8'],
            0xB1 => [&$this, 'executeOperandBx8'],
            0xB2 => [&$this, 'executeOperandBx8'],
            0xB3 => [&$this, 'executeOperandBx8'],
            0xB4 => [&$this, 'executeOperandBx8'],
            0xB5 => [&$this, 'executeOperandBx8'],
            0xB6 => [&$this, 'executeOperandBx8'],
            0xB7 => [&$this, 'executeOperandBx8'],
            0xB8 => [&$this, 'executeOperandBx'],
            0xB9 => [&$this, 'executeOperandBx'],
            0xBA => [&$this, 'executeOperandBx'],
            0xBB => [&$this, 'executeOperandBx'],
            0xBC => [&$this, 'executeOperandBx'],
            0xBD => [&$this, 'executeOperandBx'],
            0xBE => [&$this, 'executeOperandBx'],
            0xBF => [&$this, 'executeOperandBx'],
            0xC6 => [&$this, 'executeOperandC6'],
            0xC7 => [&$this, 'executeOperandC7'],
        ];
    }

    /**
     * Performs a MOV for an 8-bit registry to ModRM byte.
     *
     * Implements MOV r/m8,reg8 for the opcode \x88.
     * This is the alternate encoding of MOV \x8a.
     */
    public function executeOperand88(): bool
    {
        return $this->executeMovWithEncodingMr(Simulator::TYPE_BYTE);
    }

    /**
     * Performs a MOV for a non-8-bit registry to ModRM byte.
     *
     * Implements MOV r/m,reg for the opcode \x89.
     * This is the alternate encoding of MOV \x8b.
     */
    public function executeOperand89(): bool
    {
        $opSize = $this->getOperandSize();
        return $this->executeMovWithEncodingMr($opSize);
    }

    /**
     * Performs a MOV for an 8-bit registry to ModRM byte.
     *
     * Implements MOV reg,r/m for the opcode \x8a.
     */
    public function executeOperand8a(): bool
    {
        return $this->executeMovWithEncodingRm(Simulator::TYPE_BYTE);
    }

    /**
     * Performs a MOV for a non-8-bit registry to ModRM byte.
     *
     * Implements MOV reg,r/m for the opcode \x8b.
     */
    public function executeOperand8b(): bool
    {
        $opSize = $this->getOperandSize();
        return $this->executeMovWithEncodingRm($opSize);
    }

    /**
     * Performs a MOV for an 8-bit memory address.
     *
     * Implements MOV reg,imm8 for the opcode range \xB0-\xB7.
     */
    public function executeOperandBx8(): bool
    {
        return $this->executeMovWithEncodingOi(Simulator::TYPE_BYTE);
    }

    /**
     * Performs a MOV for a non-8-bit memory address.
     *
     * Implements MOV reg,imm for the opcode range \xB8-\xBF.
     */
    public function executeOperandBx(): bool
    {
        $opSize = $this->getOperandSize();
        return $this->executeMovWithEncodingOi($opSize);
    }

    /**
     * Performs a MOV for an 8-bit immediate to ModRM byte.
     *
     * Implements MOV r/m,imm8 for the opcode \xC6.
     */
    public function executeOperandC6(): bool
    {
        return $this->executeMovWithEncodingMi(Simulator::TYPE_BYTE);
    }

    /**
     * Performs a MOV for a non-8-bit immediate to ModRM byte.
     *
     * Implements MOV r/m,imm16/32 for the opcode \xC7.
     */
    public function executeOperandC7(): bool
    {
        $opSize = $this->getOperandSize();
        return $this->executeMovWithEncodingMi($opSize);
    }

    /**
     * Provides MOV implementation for operations with parameter encoding "MI".
     */
    private function executeMovWithEncodingMi(int $opSize): bool
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $byte = $this->parseModRmByte($sim->getCodeAtInstructionPointer(1));
        $sim->advanceInstructionPointer(1);

        /**
        * Depending on the value of the mod bit, we may be writing to a
        * register or a memory address. If our ModRM mod field is 0b11, we
        * write our value from the register. Otherwise, we are supposed to
        * write to a memory location. We will skip that bit.
        */
        if (0b11 !== $byte["mod"]) {
            $address = $this->parseAddress($byte);
            $sim->advanceInstructionPointer($address->getDisplacement());
        }

        // Now we load the Immediate value
        $immediate = $sim->getCodeAtInstructionPointer($opSize / 8);
        $value = $this->unpackImmediate($immediate, $opSize);

        $sim->advanceInstructionPointer($opSize / 8);

        if (0b11 === $byte["mod"]) {
            $rex = $sim->getRex();
            $rexSet = (bool) ($rex & Simulator::REX);
            $rmExt = (bool) ($rex & Simulator::REX_B);

            $rm = Register::getByCode($byte["rm"], $opSize, $rexSet, $rmExt);
            $sim->writeRegister($rm, $value);
        }
        // This is where we *should* save to a memory location. We won't.

        return true;
    }

    /**
    * Provides MOV implementation for operations with parameter encoding "MR".
     */
    private function executeMovWithEncodingMr(int $opSize): bool
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $byte = $this->parseModRmByte($sim->getCodeAtInstructionPointer(1));
        $sim->advanceInstructionPointer(1);

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_R);
        $rmExt = (bool) ($rex & Simulator::REX_B);

        $reg = Register::getByCode($byte["reg"], $opSize, $rexSet, $regExt);

        // We have an address
        if (0b11 !== $byte['mod']) {
            $address = $this->parseAddress($byte);
            $sim->advanceInstructionPointer($address->getDisplacement());
            // This would write to a memory location. We will not be doing that.
        } else {
            $rm = Register::getByCode($byte["rm"], $opSize, $rexSet, $rmExt);

            $value = $sim->readRegister($reg);
            $sim->writeRegister($rm, $value);
        }

        return true;
    }

    /**
    * Provides MOV implementation for operations with parameter encoding "OI".
     */
    private function executeMovWithEncodingOi(int $opSize): bool
    {
        $sim = $this->getSimulator();

        // Get the last bit of the operand.
        $opcode = ord($sim->getCodeAtInstructionPointer(1)) & 0x7;
        $sim->advanceInstructionPointer(1);

        $value = $sim->getCodeAtInstructionPointer($opSize / 8);
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
    * Provides MOV implementation for operations with parameter encoding "RM".
     */
    private function executeMovWithEncodingRm(int $opSize): bool
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $byte = $this->parseModRmByte($sim->getCodeAtInstructionPointer(1));
        $sim->advanceInstructionPointer(1);

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_R);

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
