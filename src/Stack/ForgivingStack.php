<?php
/**
 * This defines a forgiving stack structure that will not throw exceptions on
 * overflow or underflow.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Stack;

use shanept\AssemblySimulator\Exception;

/**
 * This stack will operate in quite a loose manner. Unline the strict stack, it
 * will not throw exceptions on overflow or underflow. This allows for loose
 * assembly language interpretation.
 *
 * @author Shane Thompson
 */
class ForgivingStack extends StrictStack
{
    /**
     * {@inheritDoc}
     */
    public function getOffset(int $offset, int $length): string
    {
        try {
            return parent::getOffset($offset, $length);
        } catch (Exception\StackUnderflow $e) {
            return '';
        } catch (Exception\StackIndex $e) {
            return '';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setOffset(int $offset, string $value): void
    {
        try {
            parent::setOffset($offset, $value);
        } catch (Exception\StackUnderflow $e) {
            return;
        } catch (\RangeException $e) {
            return;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clearOffset(int $offset, int $length): void
    {
        try {
            parent::clearOffset($offset, $length);
        } catch (Exception\StackUnderflow $e) {
            return;
        } catch (Exception\StackIndex $e) {
            return;
        }
    }
}
