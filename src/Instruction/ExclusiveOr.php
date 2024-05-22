<?php
/**
 * This defines the implementation for the ExclusiveOr instruction.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

use shanept\AssemblySimulator\Flags;
use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;

/**
 * Implements the ExclusiveOr instruction.
 *
 * @see https://www.felixcloutier.com/x86/xor
 * @see https://pdos.csail.mit.edu/6.828/2005/readings/i386/XOR.htm
 *
 * @author Shane Thompson
 */
class ExclusiveOr extends AssemblyInstruction
{
    public function register()
    {
        return [
            0x31 => [&$this, 'executeOperand31'],
            0x33 => [&$this, 'executeOperand33'],
        ];
    }

    private function preExecXorModRM()
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $byte = $sim->getCodeAtInstruction(1);
        $sim->advanceInstructionPointer(1);

        $byte = $this->parseModRmByte($byte);
        if (0b11 !== $byte["mod"]) {
            $message = sprintf(
                "XOR expected modrm mod byte to be 0x3, 0x%x received.",
                $byte["mod"],
            );

            throw new \RuntimeException($message);
        }

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_R);
        $rmExt = (bool) ($rex & Simulator::REX_B);

        $opSize = $this->getOperandSize();
        $reg = Register::getByCode($byte["reg"], $opSize, $rexSet, $regExt);
        $rm = Register::getByCode($byte["rm"], $opSize, $rexSet, $rmExt);

        return [
            'reg' => $reg,
            'rm' => $rm,
        ];
    }

    /**
     * Performs an xor operation on two registers.
     *
     * Implements XOR source,dest for the xor opcode \x31. Result stored in rm.
     *
     * @return bool
     */
    public function executeOperand31()
    {
        $sim = $this->getSimulator();

        $modrm = $this->preExecXorModRM();

        $regVal = $sim->readRegister($modrm['reg']);
        $rmVal = $sim->readRegister($modrm['rm']);

        $result = $rmVal ^ $regVal;

        $sim->writeRegister($modrm['rm'], $result);
        $this->operationResult($result);

        $sim->setFlag(Flags::CF, 0);
        $sim->setFlag(Flags::OF, 0);

        return true;
    }

    /**
     * Performs an xor operation on two registers.
     *
     * Implements XOR source,dest for the xor opcode \x33. Result stored in reg.
     *
     * @return bool
     */
    public function executeOperand33()
    {
        $sim = $this->getSimulator();

        $modrm = $this->preExecXorModRM();

        $regVal = $sim->readRegister($modrm['reg']);
        $rmVal = $sim->readRegister($modrm['rm']);

        $result = $regVal ^ $rmVal;

        $sim->writeRegister($modrm['reg'], $result);
        $this->operationResult($result);

        $sim->setFlag(Flags::CF, 0);
        $sim->setFlag(Flags::OF, 0);

        return true;
    }

    private function operationResult($result)
    {
        $sim = $this->getSimulator();

        /**
         * Parity Flag
         * If the number of set bits in the result is odd, the parity flag will
         * be set to 1. Otherwise, if it were even, the parity flag will be 0.
         */
        $numSetBits = substr_count(decbin($result), "1");
        $sim->setFlag(Flags::PF, ~$numSetBits & 0x1);

        /**
         * Sign Flag
         * If the result of the last operation was negative, set this to 1.
         * Otherwise, set it to 0.
         */
        $sim->setFlag(Flags::SF, $result < 0 ? 1 : 0);

        /**
         * Zero Flag
         * If the result is 0, this flag is set to 1. Otherwise it is set to 0.
         */
        $sim->setFlag(Flags::ZF, $result === 0 ? 1 : 0);
    }
}
