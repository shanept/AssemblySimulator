<?php
/**
 * This defines the basic implementation for an assembly instruction.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Address\SibAddress;
use shanept\AssemblySimulator\Address\RipAddress;

/**
 * Provides a base class for assembly instructions to build upon.
 *
 * @author Shane Thompson
 */
abstract class AssemblyInstruction
{
    private $simulator;

    /**
     * Returns an array of opcode - callback mappings to be registered with the
     * simulator.
     *
     * @return callable[]
     */
    abstract public function register();

    public function setSimulator(Simulator $simulator)
    {
        $this->simulator = $simulator;
        $simulator->registerInstructions($this, $this->register());
    }

    protected function getSimulator()
    {
        return $this->simulator;
    }

    /**
     * Determines the size of the operand to use for this instruction.
     *
     * @return int
     */
    protected function getOperandSize()
    {
        $maxWidth = $this->simulator->getLargestInstructionWidth();
        $size = min($maxWidth, Simulator::TYPE_DWRD);

        $mode = $this->simulator->getMode();
        $prefix = $this->simulator->getPrefix();
        $rex = $this->simulator->getRex();

        // On x64, prefix 0x66 specifies the 16-bit register.
        if (Simulator::LONG_MODE === $mode && 0x66 === $prefix) {
            return Simulator::TYPE_WORD;
        }

        // If we are operating with REX_W, promote operation to 64-bits.
        if ($rex & Simulator::REX_W) {
            $size = Simulator::TYPE_QUAD;
        }

        return $size;
    }

    /**
     * We don't really use this function yet... When it is in use, remove this
     * codeCoverage command.
     * @codeCoverageIgnore
     */
    protected function getAddressSize()
    {
        $maxWidth = $this->getLargestInstructionWidth();

        $mode = $this->simulator->getMode();
        $prefix = $this->simulator->getPrefix();

        // On x64, prefix 0x67 specifies a 32-bit address.
        // On x32, prefix 0x67 specifies a 16-bit address.
        if (0x67 === $prefix && Simulator::REAL_MODE !== $mode) {
            return $maxWidth / 2;
        }

        return $maxWidth;
    }

    /**
     * Parses a specified byte, returning the two registers on which to operate.
     *
     * @see https://bob.cs.sonoma.edu/IntroCompOrg-x64/bookch9.html#x27-1170009.3.4
     *
     * @param string $byte The byte to parse
     *
     * @return int[] The two registers codes.
     */
    protected function parseModRmByte($byte)
    {
        $byte = ord($byte);

        return [
            "mod" => ($byte & 0b11000000) >> 6,
            "reg" => ($byte & 0b00111000) >> 3,
            "rm" => $byte & 0b00000111,
        ];
    }

    protected function unpackImmediate($immediate, $size)
    {
        $packs = [
            16 => "vimm",
            32 => "Vimm",
            64 => "Pimm",
        ];

        return unpack($packs[$size], $immediate)["imm"];
    }

    /**
     * Translates the address for RIP or SIB based addressing modes.
     *
     * @param array $byte The parsed ModRM byte.
     *
     * @return int An addressing mode resolver.
     */
    protected function parseAddress($byte)
    {
        $sim = $this->simulator;
        $mode = $sim->getMode();

        /**
         * If the r/m byte is 0x4, we have SIB addressing.
         * If the r/m byte is 0x5, we have RIP addressing.
         */
        if (0x4 === $byte["rm"]) {
            /**
             * Calculate the displacement of the SIB operation. The offset
             * is specified by the ModRM mod byte:
             *
             * If mod = 0b00, displacement is 0.
             * If mod = 0b01, displacement is 1.
             * If mod = 0b10, displacement is 4.
             */
            $disp = 2 == $byte["mod"] ? 4 : $byte["mod"];

            $sibByte = $sim->getCodeAtInstruction(1);

            $address = new SibAddress(
                $sim->getRex(),
                $sim->getPrefix(),
                $sim->getRawRegisters(),
                $sibByte,
                $disp,
                0,
            );

            return $address;
        } elseif (0x5 === $byte["rm"] && Simulator::LONG_MODE === $mode) {
            $address = $sim->getCodeAtInstruction(4);
            $address = $this->unpackImmediate($address, Simulator::TYPE_DWRD);

            /**
             * We have a memory operand. Calculate the offset.
             *
             * If mod = 0b00, address is specified in rm.
             * If mod = 0b01, address is specified in rm, plus 8-bit offset.
             * If mod = 0b10, address is specified in rm, plus 16-bit offset.
             */
            $offset = $byte["mod"] * 8;

            $instructionPointer = $sim->getInstructionPointer();
            $address = new RipAddress($instructionPointer, $address, $offset);

            return $address;
        }

        $modes = Simulator::LONG_MODE === $sim->getMode() ? "0x4 or 0x5" : "0x4";

        $message = sprintf(
            "Invalid addressing mode. Expected %s, got 0x%x.",
            $modes,
            $byte["rm"],
        );

        throw new \OutOfRangeException($message);
    }
}
