<?php
/**
 * Defines a SIB address resolver.
 *
 * @author Shane Thompson
 */
declare(strict_types=1);

namespace shanept\AssemblySimulator\Address;

use UnexpectedValueException;

/**
 * Resolves SIB addresses.
 *
 * @author Shane Thompson
 */
class SibAddress implements AddressInterface
{
    /**
     * Stores the SIB byte.
     *
     * @var int[]
     */
    private array $sib;

    /**
     * The displacement of the address from the SIB formula.
     *
     * @var int
     */
    private int $displacement;

    /**
     * The size of the SIB byte address in bytes.
     *
     * @var int
     */
    private int $sibSize;

    /**
     * @param int[]  $sib     The parsed SIB byte.
     * @param int    $disp    The displacement of the address.
     * @param int    $sibSize How many bytes the SIB address consumes.
     *
     * @throws \UnexpectedValueException If SIB scale is not 1, 2, 4 or 8.
     * @throws \UnexpectedValueException If sibSize is not 1, 2 or 5.
     */
    public function __construct(array $sib, int $disp, int $sibSize)
    {
        if (! in_array($sib['s'], [1, 2, 4, 8], true)) {
            $message = sprintf(
                'Invalid SIB scale value %d. Expected 1, 2, 4 or 8.',
                $sib['s'],
            );

            throw new UnexpectedValueException($message);
        }

        if (! in_array($sibSize, [1, 2, 5], true)) {
            $message = sprintf(
                'Invalid SIB address length %d. Expected 1 (no displacement), ' .
                '2 (8-bit displacement) or 5 (32-bit displacement).',
                $sibSize,
            );

            throw new UnexpectedValueException($message);
        }

        $this->sib = $sib;
        $this->displacement = $disp;
        $this->sibSize = $sibSize;
    }

    /**
     * {@inheritDoc}
     */
    public function getAddress(int $offset = 0): int
    {
        $scale = $this->sib['s'];
        $index = $this->sib['i'];
        $base = $this->sib['b'];

        $dispSize = $this->sibSize - 1;
        $disp = $this->displacement;

        // Handle two's compliment addresses.
        if ($dispSize) {
            // This will generate the appropriate sized mask for the operation size.
            $dispMask = (256 ** $dispSize) - 1;
            $dispShift = (8 * $dispSize) - 1;

            // If the most significant bit is set, we have a 2's complement.
            if (($disp >> $dispShift) & 1) {
                $disp = -(((~$disp) & $dispMask) + 1);
            }
        }

        $calculatedSib = ($scale * $index) + $base + $disp;

        return $calculatedSib + $offset;
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplacement(): int
    {
        return $this->sibSize;
    }
}
