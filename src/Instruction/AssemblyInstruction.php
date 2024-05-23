<?php
/**
 * This defines the basic implementation for an assembly instruction.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Address\SibAddress;
use shanept\AssemblySimulator\Address\RipAddress;
use shanept\AssemblySimulator\Address\AddressInterface;

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
    abstract public function register(): array;

    public function setSimulator(Simulator $simulator)
    {
        $this->simulator = $simulator;
        $simulator->registerInstructions($this, $this->register());
    }

    /**
     * Returns the simulator with which this AssemblyInstruction is registered.
     */
    protected function getSimulator(): Simulator
    {
        return $this->simulator;
    }

    /**
     * Determines the size of the operand to use for this instruction.
     */
    protected function getOperandSize(): int
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
    protected function getAddressSize(): int
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
    protected function parseModRmByte(string $byte): array
    {
        $byte = ord($byte);

        return [
            "mod" => ($byte & 0b11000000) >> 6,
            "reg" => ($byte & 0b00111000) >> 3,
            "rm" => $byte & 0b00000111,
        ];
    }

    /**
     * Parses a specific SIB byte, resolving values from the registers, and
     * returning the values as an array,
     *
     * @see https://wiki.osdev.org/X86-64_Instruction_Encoding#SIB
     *
     * @param string $byte  The byte to parse.
     * @param array  $modrm The ModRM byte (optional).
     *
     * @return int[] The SIB byte values.
     */
    protected function parseSibByte(string $byte, array $modrm): array
    {
        $sim = $this->simulator;

        $byte = ord($byte);
        $sib = [
            's' => ($byte & 0b11000000) >> 6,
            'i' => ($byte & 0b00111000) >> 3,
            'b' => $byte & 0b00000111,
        ];

        $mode = $sim->getMode();
        $opSize = $this->getOperandSize();

        $rex = $sim->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $idxExt = (bool) ($rex & Simulator::REX_X);
        $bseExt = (bool) ($rex & Simulator::REX_B);

        /**
         * Scale represents the multiplier for the index.
         *
         * If scale bit == 0b00, scale is 1.
         * If scale bit == 0b01, scale is 2.
         * If scale bit == 0b10, scale is 4.
         * If scale bit == 0b11, scale is 8.
         */
        $scale = 2 ** $sib['s'];

        /**
         * Index and Base both refer to registers, in the same fashion as
         * ModRM bytes.
         *
         * If the value of Index is 4 and the REX.X extended index bit is unset,
         * we do not apply a scaled index. Thus we will override the index to 0,
         * making the index scale to 0 as well. If the REX.X bit is set, the
         * above no longer applies.
         */
        $index = 0;

        if (0x4 !== $sib['i'] || $idxExt) {
            $reg = Register::getByCode($sib['i'], $opSize, $rexSet, $idxExt);
            $index = $sim->readRegister($reg);
        }

        // Default to the ModRM displacement.
        $displacement = 2 == $modrm["mod"] ? 4 : $modrm["mod"];

        /**
         * If the SIB base is 5, we are handling a special case displacement.
         * Otherwise, we simply read from the registers.
         *
         * @see http://ref.x86asm.net/coder64.html#sib64_base_101
         * @see http://www.c-jump.com/CIS77/CPU/x86/X77_0090_addressing_modes.htm
         */
        if (
            0x5 === $sib['b'] &&
            0b00 === $modrm['mod'] &&
            Simulator::REAL_MODE !== $mode
        ) {
            // Override displacement (disp32 only).
            $base = 0;
            $displacement = 4;
        } elseif (
            0x5 === $sib['b'] &&
            0b01 === $modrm['mod'] &&
            Simulator::REAL_MODE !== $mode
        ) {
            // Override displacement - EBP + disp8 (however we do not actually displace 8 bits)
            $displacement = 0;
            $reg = Register::getByCode($sib['b'], $opSize, $rexSet, $bseExt);
            $base = $sim->readRegister($reg);
        } else {
            $reg = Register::getByCode($sib['b'], $opSize, $rexSet, $bseExt);
            $base = $sim->readRegister($reg);
        }

        return [
            's' => $scale,
            'i' => $index,
            'b' => $base,
            'displacement' => $displacement,
        ];
    }

    /**
     * Unpacks a binary encoded string into an unsigned integer, width depending
     * on the $size parameter.
     *
     * @param string $immediate The binary encoded number in string format.
     * @param int    $size      The bit-width of the $immediate (8, 16, 32, 64).
     */
    protected function unpackImmediate(string $immediate, int $size): int
    {
        $format = [
            8 => "Cimm",
            16 => "vimm",
            32 => "Vimm",
            64 => "Pimm",
        ];

        return unpack($format[$size], $immediate)["imm"];
    }

    /**
     * Translates the address for RIP or SIB based addressing modes.
     *
     * @param array $byte The parsed ModRM byte.
     *
     * @return int An addressing mode resolver.
     */
    protected function parseAddress(array $byte): AddressInterface
    {
        $mode = $this->simulator->getMode();

        /**
         * If the r/m byte is 0x4, we have SIB addressing.
         * If the r/m byte is 0x5, we have RIP addressing.
         */
        if (0x4 === $byte["rm"]) {
            return $this->parseSibAddress($byte);
        } elseif (0x5 === $byte["rm"] && Simulator::LONG_MODE === $mode) {
            return $this->parseRipAddress($byte);
        }

        $modes = (Simulator::LONG_MODE === $mode ? "0x4 or 0x5" : "0x4");

        $message = sprintf(
            "Invalid addressing mode. Expected %s, got 0x%x.",
            $modes,
            $byte["rm"],
        );

        throw new \OutOfRangeException($message);
    }

    /**
     * Parses the SIB address at the current position.
     *
     * @internal
     *
     * @param array $byte The ModRM byte.
     */
    private function parseSibAddress(array $byte): SibAddress
    {
        $sim = $this->simulator;

        $sibByte = $sim->getCodeAtInstruction(1);
        $sib = $this->parseSibByte($sibByte, $byte);

        /**
         * Calculate the displacement of the SIB operation. The offset
         * is specified by the ModRM mod byte:
         *
         * If mod = 0b00, displacement is 0.
         * If mod = 0b01, displacement is 1.
         * If mod = 0b10, displacement is 4.
         */
        $dispSize = $sib['displacement'];
        $instructionPointer = $sim->getInstructionPointer();

        if ($dispSize) {
            $dispOffset = $instructionPointer + 1;
            $displacement = $sim->getCodeBuffer($dispOffset, $dispSize);
            $displacement = $this->unpackImmediate($displacement, $dispSize * 8);
        } else {
            $displacement = 0;
        }

        $address = new SibAddress(
            $instructionPointer,
            $sib,
            $displacement,
            $dispSize + 1,
        );

        return $address;
    }

    /**
     * Parses the RIP address at the current position.
     *
     * @internal
     *
     * @param array $byte The ModRM byte.
     */
    private function parseRipAddress(array $byte): RipAddress
    {
        $address = $this->simulator->getCodeAtInstruction(4);
        $address = $this->unpackImmediate($address, Simulator::TYPE_DWRD);

        /**
         * We have a memory operand. Calculate the offset.
         *
         * If mod = 0b00, address is specified in rm.
         * If mod = 0b01, address is specified in rm, plus 8-bit offset.
         * If mod = 0b10, address is specified in rm, plus 16-bit offset.
         */
        $offset = $byte["mod"] * 8;
        $instructionPointer = $this->simulator->getInstructionPointer();

        return new RipAddress($instructionPointer, $address, $offset);
    }
}
