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
use shanept\AssemblySimulator\Address\ModRmAddress;
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
     * Determines the size of the operand to use for this instruction, in bits.
     */
    protected function getOperandSize(): int
    {
        $maxWidth = $this->simulator->getLargestInstructionWidth();
        $size = min($maxWidth, Simulator::TYPE_DWRD);

        $mode = $this->simulator->getMode();
        $rex = $this->simulator->getRex();

        /**
         * On LONG and PROTECTED mode, 0x66 specifies 16-bit operations.
         * On REAL mode, 0x66 specifies 32-bit operations.
         */
        if ($this->simulator->hasPrefix(0x66)) {
            $size = Simulator::REAL_MODE === $mode ?
                    Simulator::TYPE_DWRD :
                    Simulator::TYPE_WORD;
        }

        // If we are operating with REX_W, promote operation to 64-bits.
        if ($rex & Simulator::REX_W) {
            $size = Simulator::TYPE_QUAD;
        }

        return $size;
    }

    /**
     * Determines the size of the address to use for this instruction, in bits.
     */
    protected function getAddressSize(): int
    {
        $maxWidth = $this->simulator->getLargestInstructionWidth();

        $mode = $this->simulator->getMode();

        /**
         * On x64, ignore this prefix.
         * On x32, prefix 0x67 specifies a 16-bit address.
         * On 16-bit, prefix 0x67 specifies a 32-bit address.
         */
        if ($this->simulator->hasPrefix(0x67) && Simulator::LONG_MODE !== $mode) {
            $maxWidth = Simulator::REAL_MODE === $mode ?
                            Simulator::TYPE_DWRD :
                            Simulator::TYPE_WORD;
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
        $byte = ord($byte);
        $sib = [
            's' => ($byte & 0b11000000) >> 6,
            'i' => ($byte & 0b00111000) >> 3,
            'b' => $byte & 0b00000111,
        ];

        $mode = $this->simulator->getMode();
        $opSize = $this->getOperandSize();

        $rex = $this->simulator->getRex();
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
            $index = $this->simulator->readRegister($reg);
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
            $base = $this->simulator->readRegister($reg);
        } else {
            $reg = Register::getByCode($sib['b'], $opSize, $rexSet, $bseExt);
            $base = $this->simulator->readRegister($reg);
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
        $isLong = Simulator::LONG_MODE === $this->simulator->getMode();

        /**
         * If the r/m byte is 0x4, we have SIB addressing.
         * If the r/m byte is 0x5, we have RIP addressing.
         * Otherwise the address is in the register referenced by r/m,
         * offset determined by mod.
         */
        if (0x4 === $byte['rm']) {
            return $this->parseSibAddress($byte);
        } elseif (0x5 === $byte['rm'] && 0 === $byte['mod'] && $isLong) {
            return $this->parseRipAddress($byte);
        } else {
            return $this->parseMemoryOffset($byte);
        }
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

        $instructionPointer = $this->simulator->getInstructionPointer();

        return new RipAddress($instructionPointer, $address);
    }

    private function parseMemoryOffset(array $byte): ModRmAddress
    {
        $rex = $this->simulator->getRex();
        $rexSet = (bool) ($rex & Simulator::REX);
        $bseExt = (bool) ($rex & Simulator::REX_B);

        $opSize = $this->getOperandSize();
        $reg = Register::getByCode($byte['rm'], $opSize, $rexSet, $bseExt);
        $address = $this->simulator->readRegister($reg);

        $dispSize = 2 == $byte["mod"] ? 4 : $byte["mod"];
        $displacement = 0;

        /**
         * If we are on 64-bit and receive a mod of 0 and an rm of 5, we have a
         * RIP relative address with 32-bit displacement. On a 32-bit machine,
         * we just have a near with 32-bit displacement.
         */
        if (0x5 === $byte['rm'] && 0x0 === $byte['mod']) {
            $dispSize = 4;
        }

        if ($dispSize) {
            $dispOffset = $this->simulator->getInstructionPointer();
            $displacement = $this->simulator->getCodeBuffer($dispOffset, $dispSize);
            $displacement = $this->unpackImmediate($displacement, $dispSize * 8);
        }

        return new ModRmAddress($address, $displacement, $dispSize);
    }

    /**
     * Returns the correct stack pointer for the Simulator mode.
     */
    protected function getStackPointerRegister()
    {
        $pointers = [
            null,
            Register::SP,
            Register::ESP,
            Register::RSP,
        ];

        return $pointers[$this->simulator->getMode()];
    }
}
