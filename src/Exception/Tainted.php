<?php
/**
 * Defines an exception for operating a tainted environment.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator\Exception;

use LogicException;

/**
 * This exception is thrown in instances where we attempt to operate a tainted
 * environment.
 *
 * @author Shane Thompson
 */
class Tainted extends LogicException {}
