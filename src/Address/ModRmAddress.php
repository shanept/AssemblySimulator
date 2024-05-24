<?php
/**
 * Defines a ModRM address resolver.
 *
 * @author Shane Thompson
 */
declare(strict_types=1);

namespace shanept\AssemblySimulator\Address;

/**
 * Resolves ModRM register addresses.
 *
 * @author Shane Thompson
 */
class ModRmAddress implements AddressInterface
{
    /**
     * Contains the base address read from the register.
     *
     * @var int
     */
    private $baseAddress;

    /**
     * Contains the offset of the base address, taken from the ModRM byte.
     *
     * @var int
     */
    private $offset;

    /**
     * Stores the size of the displacement resulting from the offset.
     *
     * @var int
     */
    private $displacement;

    /**
     * @param int $baseAddress The address as from the register.
     * @param int $offset      The offset extracted from the ModRM byte.
     * @param int $dispSize    The displacement size of the offset.
     */
    public function __construct(int $baseAddress, int $offset, int $dispSize)
    {
        $this->baseAddress = $baseAddress;
        $this->offset = $offset;
        $this->displacement = $dispSize;
    }

    public function getAddress(int $offset = 0): int
    {
        return $this->baseAddress + $this->offset + $offset;
    }

    public function getDisplacement(): int
    {
        return $this->displacement;
    }
}
