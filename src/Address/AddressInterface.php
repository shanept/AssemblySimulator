<?php
/**
 * Defines an interface for a Memory operation.
 *
 * @author Shane Thompson
 */
declare(strict_types=1);

namespace shanept\AssemblySimulator\Address;

/**
 * This is purely an interface for different types of memory addressing mode
 * resolvers.
 *
 * @author Shane Thompson
 */
interface AddressInterface
{
    /**
     * Returns a memory address, resolved with the determined addressing mode.
     *
     * @param int $offset (Optional) Adds an offset to the resolved address.
     *
     * @return int
     */
    public function getAddress(int $offset = 0): int;

    /**
     * Returns the displacement (in bytes) of the memory address.
     *
     * @return int
     */
    public function getDisplacement(): int;
}
