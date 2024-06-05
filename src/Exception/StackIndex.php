<?php
/**
 * Defines an exception for when an invalid stack index access is attempted.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Exception;

use LogicException;

/**
 * This exception is thrown in instances where we attempt to access an invalid
 * stack index.
 *
 * @author Shane Thompson
 */
class StackIndex extends LogicException {}
