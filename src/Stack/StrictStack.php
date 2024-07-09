<?php
/**
 * This defines a strict stack structure that will throw exceptions on overflow
 * or underflow.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Stack;

use RangeException;
use shanept\AssemblySimulator\Exception;

/**
 * This stack will operate in the strictest manner possible. It will throw
 * exceptions on overflow or underflow, enforcing tight adherence to quality
 * assembly code.
 *
 * @author Shane Thompson
 */
class StrictStack extends Stack
{
    /**
     * {@inheritDoc}
     *
     * @throws \shanept\AssemblySimulator\Exception\StackUnderflow
     *              If the requested address is higher than the start of the stack.
     * @throws \shanept\AssemblySimulator\Exception\StackIndex
     *              If the requested address is lower than the end of the stack.
     */
    public function getOffset(int $offset, int $length): string
    {
        if ($offset > $this->stackAddress) {
            $message = sprintf(
                'Stack underflow. Offset 0x%X requested. Stack starts at 0x%X.',
                $offset,
                $this->stackAddress,
            );

            throw new Exception\StackUnderflow($message);
        }

        $stackLength = strlen($this->stack);

        // This makes our offset relative to the start of the stack string.
        $stackOffset = $offset - ($this->stackAddress - $stackLength) - 1;

        if ($stackOffset < 0) {
            $message = sprintf(
                'Stack offset 0x%X requested, but it exceeds the top of the ' .
                'stack (0x%X)',
                $offset,
                $this->stackAddress - $stackLength + 1,
            );

            throw new Exception\StackIndex($message);
        }

        // Our offset is relative to the end of the string.
        return substr($this->stack, $stackOffset, $length);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \shanept\AssemblySimulator\Exception\StackUnderflow
     *              If the requested address is higher than the start of the stack.
     * @throws \RangeException If the operation would exceed the maximum stack size.
     */
    public function setOffset(int $offset, string $value): void
    {
        if ($offset > $this->stackAddress) {
            $message = sprintf(
                'Stack underflow. Offset 0x%X requested. Stack starts at 0x%X.',
                $offset,
                $this->stackAddress,
            );

            throw new Exception\StackUnderflow($message);
        }

        $valueLength = strlen($value);
        $stackLength = strlen($this->stack);
        $availableBytes = $this->maximumSize - $stackLength;

        // This makes our offset relative to the start of the stack string.
        $stackOffset = $offset - ($this->stackAddress - $stackLength) - 1;

        /**
         * If the requested offset is further up the stack than the amount of
         * bytes we have been provided with to write to the stack, we will
         * zero-extend the value by the required amount.
         */
        if ($stackOffset < 0) {
            $newBytesLength = ($stackOffset * -1);

            if ($newBytesLength > $availableBytes) {
                $message = sprintf(
                    'Exceeded maximum stack size. Attempted to allocate %d ' .
                    'new bytes to the stack, however it exceeds the maximum ' .
                    'stack size of %d.',
                    $newBytesLength,
                    $this->maximumSize,
                );

                throw new RangeException($message);
            }

            $zeroPadAmount = $newBytesLength - $valueLength;

            if ($zeroPadAmount > 0) {
                $value .= str_repeat("\0", $zeroPadAmount);
            }

            // Prepend our value to the stack.
            $this->stack = $value . $this->stack;
        } else {
            // We must overwrite the stack at the specified position.
            $this->stack = substr_replace(
                $this->stack,
                $value,
                $stackOffset,
                $valueLength,
            );
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws \shanept\AssemblySimulator\Exception\StackUnderflow
     *              If the requested address is higher than the start of the stack.
     * @throws \shanept\AssemblySimulator\Exception\StackIndex
     *              If the requested address is lower than the end of the stack.
     */
    public function clearOffset(int $offset, int $length): void
    {
        if ($offset > $this->stackAddress) {
            $message = sprintf(
                'Stack underflow. Offset 0x%X requested. Stack starts at 0x%X.',
                $offset,
                $this->stackAddress,
            );

            throw new Exception\StackUnderflow($message);
        }

        $stackLength = strlen($this->stack);

        // This makes our offset relative to the start of the stack string.
        $stackOffset = $offset - ($this->stackAddress - $stackLength) - 1;

        if ($stackOffset < 0) {
            $message = sprintf(
                'Stack offset 0x%X requested, but it exceeds the top of the ' .
                'stack (0x%X)',
                $offset,
                $this->stackAddress - $stackLength,
            );

            throw new Exception\StackIndex($message);
        } elseif ($stackOffset === 0) {
            $this->stack = substr($this->stack, $length);
        } else {
            $clearString = str_repeat("\0", $length);

            // Replace the offset with NUL bytes.
            $this->stack = substr_replace(
                $this->stack,
                $clearString,
                $stackOffset,
                $length,
            );
        }
    }
}
