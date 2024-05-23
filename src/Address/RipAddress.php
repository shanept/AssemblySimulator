<?php
/**
 * Defines a RIP-relative address resolver.
 *
 * @author Shane Thompson
 */
declare(strict_types=1);

namespace shanept\AssemblySimulator\Address;

/**
 * Resolves RIP-relative addresses for LONG_MODE operation.
 *
 * @author Shane Thompson
 */
class RipAddress implements AddressInterface
{
    /**
     * Contains the contents of the RIP register when the address was found.
     *
     * @var int
     */
    private $rip;

    /**
     * Contains the relative address, pointing to the end of the instruction.
     *
     * @var int
     */
    private $address;

    /**
     * The instruction displacement. It is always 32-bits (4 bytes) for RIP
     * relative addressing.
     *
     * @var int
     */
    private $displacement = 4;

    /**
     * @param int $ripRegister The contents of the instruction pointer register.
     * @param int $address     The 32-bit RIP-relative address.
     * @param int $offset      The offset to the end of this instruction.
     */
    public function __construct(int $ripRegister, int $address, int $offset)
    {
        $this->rip = $ripRegister;
        $this->address = $address + $offset;
    }

    public function getAddress(int $offset = 0): int
    {
        // Handle two's compliment addresses.
        $address = $this->address;

        // If the most significant bit is set, we have a 2's complement.
        if (($address >> 31) & 1) {
            $address = -(((~$address) & 0xFFFFFFFF) + 1);
        }

        return $this->rip + $address + $this->displacement + $offset;
    }

    public function getDisplacement(): int
    {
        return $this->displacement;
    }
}
