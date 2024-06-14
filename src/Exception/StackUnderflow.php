<?php
/**
 * Defines an exception for when a stack underflow is enountered.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Exception;

use UnderflowException;

/**
 * This exception is thrown in instances where a stack underflow occurs.
 *
 * @author Shane Thompson
 */
class StackUnderflow extends UnderflowException {}
