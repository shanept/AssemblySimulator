<?php
/**
 * Defines an exception for when an invalid opcode is enountered.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Exception;

use OutOfBoundsException;

/**
 * This exception is thrown in instances where an invalid opcode is encountered.
 *
 * @author Shane Thompson
 */
class InvalidOpcode extends OutOfBoundsException {}
