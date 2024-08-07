<?php
/**
 * This defines the implementation for the ExclusiveOr instruction.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

use RuntimeException;
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
    /**
     * {@inheritDoc}
     */
    public function register(): array
    {
        return [
            0x30 => [&$this, 'executeOperand30'],
            0x31 => [&$this, 'executeOperand31'],
            0x32 => [&$this, 'executeOperand32'],
            0x33 => [&$this, 'executeOperand33'],
        ];
    }

    /**
     * Performs an xor operation on two registers.
     *
     * Implements XOR source8,dest8 for the xor opcode \x30. Result stored in rm.
     */
    public function executeOperand30(): bool
    {
        return $this->executeXorWithEncodingMr(Simulator::TYPE_BYTE);
    }

    /**
     * Performs an xor operation on two registers.
     *
     * Implements XOR source,dest for the xor opcode \x31. Result stored in rm.
     */
    public function executeOperand31(): bool
    {
        $opSize = $this->getOperandSize();
        return $this->executeXorWithEncodingMr($opSize);
    }

    /**
     * Performs an xor operation on two registers.
     *
     * Implements XOR source8,dest8 for the xor opcode \x32. Result stored in reg.
     */
    public function executeOperand32(): bool
    {
        return $this->executeXorWithEncodingRm(Simulator::TYPE_BYTE);
    }

    /**
     * Performs an xor operation on two registers.
     *
     * Implements XOR source,dest for the xor opcode \x33. Result stored in reg.
     */
    public function executeOperand33(): bool
    {
        $opSize = $this->getOperandSize();
        return $this->executeXorWithEncodingRm($opSize);
    }

    /**
     * @throws \RuntimeException If MOD byte is not 0x3.
     *
     * @return array{"reg": RegisterObj, "rm": RegisterObj}
     */
    private function preExecXorModRM(int $opSize): array
    {
        $sim = $this->getSimulator();
        $sim->advanceInstructionPointer(1);

        $byte = $sim->getCodeAtInstructionPointer(1);
        $sim->advanceInstructionPointer(1);

        $byte = $this->parseModRmByte($byte);
        if (0b11 !== $byte["mod"]) {
            $message = sprintf(
                "XOR expected modrm mod byte to be 0x3, 0x%x received.",
                $byte["mod"],
            );

            throw new RuntimeException($message);
        }

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $regExt = (bool) ($rex & Simulator::REX_R);
        $rmExt = (bool) ($rex & Simulator::REX_B);

        $reg = Register::getByCode($byte["reg"], $opSize, $rexSet, $regExt);
        $rmReg = Register::getByCode($byte["rm"], $opSize, $rexSet, $rmExt);

        return [
            'reg' => $reg,
            'rm' => $rmReg,
        ];
    }

    private function executeXorWithEncodingMr(int $opSize): bool
    {
        $sim = $this->getSimulator();

        $modrm = $this->preExecXorModRM($opSize);

        $regVal = $sim->readRegister($modrm['reg']);
        $rmVal = $sim->readRegister($modrm['rm']);

        $result = $rmVal ^ $regVal;

        $sim->writeRegister($modrm['rm'], $result);
        $this->operationResult($result);

        $sim->setFlag(Flags::CF, false);
        $sim->setFlag(Flags::OF, false);

        return true;
    }

    private function executeXorWithEncodingRm(int $opSize): bool
    {
        $sim = $this->getSimulator();

        $modrm = $this->preExecXorModRM($opSize);

        $regVal = $sim->readRegister($modrm['reg']);
        $rmVal = $sim->readRegister($modrm['rm']);

        $result = $regVal ^ $rmVal;

        $sim->writeRegister($modrm['reg'], $result);
        $this->operationResult($result);

        $sim->setFlag(Flags::CF, false);
        $sim->setFlag(Flags::OF, false);

        return true;
    }

    private function operationResult(int $result): void
    {
        $sim = $this->getSimulator();

        /**
         * Parity Flag
         *
         * If the number of set bits in the result is odd, the parity flag will
         * be set. Otherwise, if it were even, the parity flag will be unset.
         */
        $numSetBits = substr_count(decbin($result), '1');
        $shouldSetParity = boolval(~$numSetBits & 0x1);
        $sim->setFlag(Flags::PF, $shouldSetParity);

        /**
         * Sign Flag
         *
         * If the result of the last operation was negative, set this flag.
         * Otherwise, unset it.
         */
        $sim->setFlag(Flags::SF, $result < 0);

        /**
         * Zero Flag
         *
         * If the result is 0, set the flag, otherwise unset it.
         */
        $sim->setFlag(Flags::ZF, $result === 0);
    }
}
