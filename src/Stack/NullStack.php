<?php
/**
 * This defines an implementation of the stack that does nothing.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Stack;

/**
 * Provides a basic implementation of a stack, that does nothing but return NUL.
 *
 * @author Shane Thompson
 */
class NullStack extends Stack
{
    /**
     * {@inheritDoc}
     */
    public function setAddress(int $address): void
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function limitSize(int $limit): void
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function getOffset(int $offset, int $length): string
    {
        return str_repeat("\0", $length);
    }

    /**
     * {@inheritDoc}
     */
    public function setOffset(int $offset, string $value): void
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function clearOffset(int $offset, int $length): void
    {
        return;
    }
}
