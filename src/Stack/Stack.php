<?php
/**
 * This defines the basic implementation for a stack structure.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Stack;

/**
 * Provides a base class for stack implementations to build upon.
 *
 * @author Shane Thompson
 */
abstract class Stack
{
    /**
     * A binary string representing the stack.
     *
     * @var string
     */
    protected string $stack = '';

    /**
     * Specifies the address at the top of the stack.
     *
     * @var int
     */
    protected int $stackAddress;

    /**
     * Specifies the maximum permissible length of the stack.
     *
     * @var int
     */
    protected int $maximumSize;

    /**
     * Returns a full copy of the internal representation of the stack.
     *
     * @final
     *
     * @return string A binary string which is the contents of the stack.
     */
    public function getStackContents(): string
    {
        return $this->stack;
    }

    /**
     * Returns the length of data pushed onto the stack.
     *
     * @final
     *
     * @return int
     */
    public function getLength(): int
    {
        return strlen($this->stack);
    }

    /**
     * Completely empties the stack.
     *
     * @final
     */
    public function clear(): void
    {
        $this->stack = '';
    }

    /**
     * Sets the address for the top of the stack.
     *
     * @final
     *
     * @param int $address
     */
    public function setAddress(int $address): void
    {
        $this->stackAddress = $address;
    }

    /**
     * Limits the stack size to $limit.
     *
     * @final
     *
     * @param int $limit
     */
    public function limitSize(int $limit): void
    {
        $currentSize = strlen($this->stack);

        /**
         * If we are attempting to reduce the size of the stack past existing
         * data on the stack, we should throw an exception.
         */
        if ($currentSize > $limit) {
            $message = sprintf(
                'Attempted to limit size of the stack to %d bytes, smaller ' .
                'than the length of the data existing on the stack (%d bytes).',
                $limit,
                $currentSize,
            );

            throw new \RuntimeException($message);
        }

        $this->maximumSize = $limit;
    }

    /**
     * Returns the value of the stack at a specified offset.
     *
     * @param int $offset The offset within the stack.
     * @param int $length The amount of bytes to read.
     *
     * @return string The value of the stack at the specified position, as a binary string.
     */
    abstract public function getOffset(int $offset, int $length): string;

    /**
     * Write to the stack at a specified offset.
     *
     * @param int    $offset The stack offset at which to write the value.
     * @param string $value  The value to write to the stack, as a binary string.
     */
    abstract public function setOffset(int $offset, string $value): void;

    /**
     * Clears the value on the stack at the specified position. Simply unsets
     * the stack offset.
     *
     * @param int $offset The offset to clear.
     * @param int $length The amount of bytes to clear (between offset and stackAddress).
     */
    abstract public function clearOffset(int $offset, int $length): void;
}
