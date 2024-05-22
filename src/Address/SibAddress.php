<?php
/**
 * Defines a SIB address resolver.
 *
 * @author Shane Thompson
 */
declare(strict_types=1);

namespace shanept\AssemblySimulator\Address;

/**
 * Resolves SIB addresses.
 *
 * @author Shane Thompson
 */
class SibAddress implements AddressInterface
{
    /**
     * Stores the offset to the end of the instruction.
     *
     * @var int
     */
    private $offset;

    /**
     * Stores the SIB byte.
     *
     * @var int[]
     */
    private $sib;

    /**
     * The displacement of the address from the SIB formula.
     *
     * @var int
     */
    private $displacement;

    /**
     * The size of the SIB byte address in bytes.
     *
     * @var int
     */
    private $sibSize;

    /**
     * @param int    $offset  The offset to the end of this instruction.
     * @param int[]  $sib     The parsed SIB byte.
     * @param int    $disp    The displacement of the address.
     * @param int    $sibSize How many bytes the SIB address consumes.
     */
    public function __construct($offset, $sib, $disp, $sibSize)
    {
        $this->offset = $offset;
        $this->sib = $sib;
        $this->displacement = $disp;
        $this->sibSize = $sibSize;
    }

    public function getAddress($offset = 0)
    {
        $scale = $this->sib['s'];
        $index = $this->sib['i'];
        $base = $this->sib['b'];

        $dispSize = $this->sibSize - 1;

        // Handle two's compliment addresses.
        if ($dispSize) {
            $disp = $this->displacement;

            // This will generate the appropriate sized mask for the operation size.
            $dispMask = (256 ** $dispSize) - 1;
            $dispShift = (8 * $dispSize) - 1;

            // If the most significant bit is set, we have a 2's complement.
            if (($disp >> $dispShift) & 1) {
                $disp = -(((~$disp) & $dispMask) + 1);
            }
        }

        return ($scale * $index) + $base + $disp + $this->sibSize + $this->offset;
    }

    public function getDisplacement()
    {
        return $this->sibSize;
    }
}
