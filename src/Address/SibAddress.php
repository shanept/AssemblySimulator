<?php
/**
 * Defines a SIB address resolver.
 *
 * @author Shane Thompson
 */
declare(strict_types=1);

namespace shanept\AssemblySimulator\Address;

use shanept\AssemblySimulator\Simulator;

/**
 * Resolves SIB addresses.
 *
 * @author Shane Thompson
 */
class SibAddress implements AddressInterface
{
    /**
     * Stores the status of the REX bit when we got to this location, or empty
     * on non-LONG modes.
     *
     * @var int
     */
    private $rex;

    /**
     * Stores the opcode prefix for the current operation.
     *
     * @var int
     */
    private $prefix;

    /**
     * Stores a copy of the registers when the SIB byte was encountered.
     *
     * @var int[]
     */
    private $registers;

    /**
     * Stores the unparsed SIB byte.
     *
     * @var int
     */
    private $sibByte;

    /**
     * The displacement of the address in bytes.
     *
     * @var int
     */
    private $displacement;

    /**
     * Stores the offset to the end of the instruction.
     *
     * @var int
     */
    private $instructionOffset;

    /**
     * @param int    $rex    The current REX bit.
     * @param int    $pref   Current opcode prefixes.
     * @param int[]  $regs   A copy of the registers.
     * @param string $sib    The unparsed SIB byte.
     * @param int    $disp   The displacement of the address.
     * @param int    $offset The offset to the end of this instruction.
     */
    public function __construct($rex, $pref, $regs, $sib, $disp, $offset)
    {
        $this->rex = $rex;
        $this->prefix = $pref;
        $this->registers = $regs;
        $this->sibByte = ord($sib);
        $this->displacement = $disp;
        $this->instructionOffset = $offset;
    }

    public function getAddress($offset = 0)
    {
        $scale = ($this->sibByte & 0b11000000) >> 6;
        $index = ($this->sibByte & 0b00111000) >> 3;
        $base = $this->sibByte & 0b00000111;

        // Switch to LONG operands if specified by REX.
        if ($this->rex & Simulator::REX_X) {
            $index += 8;
        }

        if ($this->rex & Simulator::REX_B) {
            $base += 8;
        }

        /**
         * Scale represents the multiplier for the index.
         *
         * If scale bit == 0b00, scale is 1.
         * If scale bit == 0b01, scale is 2.
         * If scale bit == 0b10, scale is 4.
         * If scale bit == 0b11, scale is 8.
         *
         * Index and Base both refer to registers, in the same fashion as
         * ModRM bytes.
         */
        $scale = 2 ** $scale;
        $index = $this->registers[$index];
        $base = $this->registers[$base];

        return $scale * $index +
            $base +
            $this->displacement +
            $offset +
            $this->instructionOffset;
    }

    public function getDisplacement()
    {
        return $this->displacement;
    }
}
