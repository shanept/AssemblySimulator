<?php
/**
 * This defines is a partial x86/x64 assembly simulator.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator;

use shanept\AssemblySimulator\Instruction\AssemblyInstruction;

/**
 * Implements an assembly simulator, capable of operating in both x86 and x64
 * modes.
 *
 * @author Shane Thompson
 */
class Simulator
{
    /**
     * Defines different register widths.
     *
     * @var int
     */
    const TYPE_BYTE = 8;
    const TYPE_WORD = 16;
    const TYPE_DWRD = 32;
    const TYPE_QUAD = 64;

    /**
     * This is our memory storage for our registers.
     *
     * @var int[]
     */
    private array $registers = [];

    /**
     * Contains the address for the start of our stack.
     *
     * @var int
     */
    private int $stackAddress = 0;

    /**
     * Specifies the maximum permissible size of the stack, in bytes.
     *
     * @var int
     */
    private int $stackSize = 0;

    /**
     * Stores our stack instance.
     *
     * @var \shanept\AssemblySimulator\Stack\Stack
     */
    private Stack\Stack $stack;

    /**
     * 32-bit register to store extended flags.
     *
     * @var int
     */
    private int $eFlags = 0;

    /**
     * This is our instruction pointer.
     * It may be referred to as IP, EIP or RIP depending on the architecture.
     *
     * @var int
     */
    private int $iPointer = 0;

    /**
     * Stores the *real* offset for the address loaded by the LEA operand.
     *
     * @var int
     */
    private int $addressBase = 0;

    /**
     * Defines the modes that we may operate within.
     *
     * @see $mode
     *
     * @var int
     */
    const REAL_MODE = 1;
    const PROTECTED_MODE = 2;
    const LONG_MODE = 3;

    /**
     * Defines a user-friendly readout of the mode in which we are running.
     *
     * @var string[]
     */
    const MODE_NAMES = ["INVALID", "real", "protected", "long"];

    /**
     * Defines the mode in which we operate.
     *
     * @var int
     */
    private int $mode;

    /**
    * Defines the REX instruction offset. Set in long mode if an instruction is
     * using REX mode.
     *
     * @var int
     */
    const REX = 0x40;

    /**
     * Extension of r/m field, base field, or opcode reg field
     *
     * @var int
     */
    const REX_B = 0x1;

    /**
     * Extension of SIB index field.
     *
     * @var int
     */
    const REX_X = 0x2;

    /**
     * Extension of ModR/M reg field
     *
     * @var int
     */
    const REX_R = 0x4;

    /**
     * 64 Bit Operand Size
     *
     * @var int
     */
    const REX_W = 0x8;

    /**
     * Stores the status of our REX bit. 64-bit only.
     *
     * @var int
     */
    private int $rex = 0;

    /**
     * Contains any array of prefixes for the current instruction.
     *
     * @var int[]
     */
    private array $prefixes = [];

    /**
     * Stores the currently simulated assembly code.
     *
     * @var string
     */
    private string $buffer = "";

    /**
     * Stores whether or not the state has changed since last reset.
     *
     * @var bool
     */
    private bool $tainted = false;

    /**
     * Stores a list of opcode - callback mappings, thus the consumer may supply
     * their own implementation for an opcode, or overload the existing
     * implementation.
     *
     * @var array<int, array{"reference": AssemblyInstruction, "mappings": array<int, callable>}>
     */
    private array $registeredInstructions = [];

    /**
     * @param ?int $mode The machine mode to operate in.
     * @param SimulatorOptions $options
     */
    public function __construct(int $mode = null, array $options = [])
    {
        $this->mode = $mode ?? self::REAL_MODE;

        // Set an appropriately high stack address, dependent on mode, where
        // the leading 4 bits are 0011.
        $this->stackAddress = 2 ** ($this->getLargestInstructionWidth() - 2) - 1;

        // Limit the maximum size of our stack to a reasonable amount.
        // Max is approx 16MB
        $this->stackSize = min(0xFFFFFF, 2 ** ((4 << $this->mode) - 1) - 1);

        $this->stack = $options['stack'] ?? new Stack\NullStack();
        $this->stack->setAddress($this->stackAddress);
        $this->stack->limitSize($this->stackSize);

        // Sets the simulator up to a known state.
        $this->reset();
    }

    /**
     * Registers instruction handlers with the simulator. Note that more recent
     * mappings take priority over older mappings. You should not be calling
     * this method directly. You should extend the AssmeblyInstruction class and
     * use the setSimulator function, which will automatically call this method
     * with the result of the instruction's register method.
     *
     * @see shanept\AssemblySimulator\Instruction\AssemblyInstruction::register
     * @see shanept\AssemblySimulator\Instruction\AssemblyInstruction::setSimulator
     *
     * @param AssemblyInstruction $reference A reference to the class implementing the instruction.
     * @param callable[]          $mappings  A list of opcode - callable mappings to register with the simulator.
     */
    public function registerInstructions(
        AssemblyInstruction $reference,
        array $mappings
    ): void {
        $record = ['reference' => $reference, 'mappings' => $mappings];
        array_unshift($this->registeredInstructions, $record);
    }

    /**
     * Clears our registers and lets us run again.
     */
    public function reset(): void
    {
        $this->tainted = false;

        $this->rex = 0;
        $this->prefixes = [];
        $this->buffer = "";

        $this->eFlags = 0;
        $this->stack->clear();

        // 16 and 32 bit use 8 registers, 64-bit uses 16.
        $numRegisters = max(8, 2 << $this->mode);
        $this->registers = array_fill(0, $numRegisters, 0);

        // Set the stack pointer to the stack address.
        $this->registers[Register::SP['offset']] = $this->stackAddress;
    }

    /**
     * Returns the mode under which we are operating.
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * Returns a user-readable name for the mode under which we are operating.
     */
    public function getModeName(): string
    {
        return self::MODE_NAMES[$this->mode];
    }

    /**
     * Sets the operating mode. ALWAYS reset the machine after doing this!
     *
     * @param int $mode The machine mode to operate in.
     */
    public function setMode(int $mode): void
    {
        $this->tainted = true;

        $this->mode = $mode;
    }

    /**
     * Sets the address of the stack. ALWAYS reset the machine after doing this!
     *
     * @param int $address The starting address of the stack.
     */
    public function setStackAddress(int $address): void
    {
        $this->tainted = true;

        $this->stackAddress = $address;
        $this->stack->setAddress($address);
    }

    /**
     * Sets the maximum size of the stack. ALWAYS reset the machine after doing this!
     *
     * @param int $size The maximum size for the stack.
     */
    public function setStackSize(int $size): void
    {
        $this->tainted = true;

        $this->stackSize = $size;
        $this->stack->limitSize($size);
    }

    /**
     * Returns the value for a given flag.
     *
     * @param int $flag The flag to retreive.
     */
    public function getFlag(int $flag): bool
    {
        $factor = $flag & -$flag;
        return (($this->eFlags & $flag) / $factor) === 1;
    }

    /**
     * Returns all flags.
     */
    public function getFlags(): int
    {
        return $this->eFlags;
    }

    /**
     * Set the value of a flag.
     *
     * @param int  $flag  The flag to be set.
     * @param bool $value The value to set the flag to.
     */
    public function setFlag(int $flag, bool $value): void
    {
        $factor = $flag & -$flag;

        // Remove the existing flag.
        $this->eFlags &= ~$flag;

        // Add the new flag.
        $this->eFlags += ($value ? 1 : 0) * $factor;
    }

    /**
     * Gets the current REX value (if applicable).
     */
    public function getRex(): int
    {
        return $this->rex;
    }

    /**
     * Gets the current instruction prefixes under which we are operating.
     *
     * @return int[]
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * Determines whether the specified prefix has been set.
     *
     * @param int $prefix The prefix to check for.
     */
    public function hasPrefix(int $prefix): bool
    {
        return in_array($prefix, $this->prefixes, true);
    }

    /**
     * Gets the memory address base.
     */
    public function getAddressBase(): int
    {
        return $this->addressBase;
    }

    /**
    * Sets the base memory address.
     *
     * @param int $base The address base.
     */
    public function setAddressBase(int $base): void
    {
        $this->addressBase = $base;
    }

    /**
     * Returns the largest allowable instruction size on this mode.
     */
    public function getLargestInstructionWidth(): int
    {
        // Returns the bit-width of our architecture.
        return 8 << $this->mode;
    }

    /**
     * Returns the raw register array.
     *
     * @return int[]
     */
    public function getRawRegisters(): array
    {
        return $this->registers;
    }

    /**
     * Returns all registers values indexed against their names.
     *
     * @return int[]
     */
    public function getIndexedRegisters(): array
    {
        $size = $this->getLargestInstructionWidth();
        $numRegisters = count($this->registers);

        $indexedRegisters = [];

        for ($i = 0; $i < $numRegisters; $i++) {
            // If we are past register 8, we are into our extended registers.
            $id = $i % 8;
            $rex = $i > 7;

            $register = Register::getByCode($id, $size, $rex, $rex);

            $indexedRegisters[$register["name"]] = $this->registers[$i];
        }

        return $indexedRegisters;
    }

    /**
     * Returns the value for the specified register.
     *
     * @param RegisterObj $register The register reference
     *
     * @return int
     */
    public function readRegister(array $register): int
    {
        $largestWidth = $this->getLargestInstructionWidth();

        $offset = $register["offset"];
        $width = $register["width"];
        $mask = $register["mask"];

        if ($width > $largestWidth) {
            $message = sprintf(
                'Register "%s" too large for mode "%s".',
                $register["name"],
                $this->getModeName(),
            );

            throw new \RuntimeException($message);
        }

        $value = $this->registers[$offset];

        if (is_int($value)) {
            $value &= $mask;
        }

        return $value;
    }

    /**
     * Writes a value to the specified register.
     *
     * @param RegisterObj $register The register we are going to write to.
     * @param int         $value    The value to write to be written.
     *
     * @throws \LogicException if we are attempting to write to a register too
     *                         large for our simulation mode. (i.e. writing to
     *                         64-bit register in 16-bit REAL mode).
     */
    public function writeRegister(array $register, int $value): void
    {
        $this->taintProtection();

        $largestWidth = $this->getLargestInstructionWidth();

        $offset = $register["offset"];
        $width = $register["width"];
        $mask = $register["mask"];

        if ($width > $largestWidth) {
            $message = sprintf(
                'Register "%s" too large for mode "%s".',
                $register["name"],
                $this->getModeName(),
            );

            throw new \LogicException($message);
        }

        /**
         * Any register operation should only overwrite $register['width'] bytes
         * on the register, thus leaving any old data there. The exception to
         * this is if we are writing 32 bits in long mode, or if the size of
         * our operation matches the width of the register.
         *
         * The ultimate result is that only 16-bit and 8-bit operations are
         * handled unless we are running REAL mode and writing a 16 bit
         * operation.
         */
        if (self::LONG_MODE !== $this->mode || $width < self::TYPE_DWRD) {
            // Our underlying register is $largestWidth bits long. We are
            // writing to $register['width'] bits at the bottom end.
            // Negate $register['width'] bits from the modeMask.
            $modeMask = Register::MASK_WIDTH[$largestWidth];

            // If the old value does not exist, it becomes 0.
            $oldValue = $this->registers[$offset];

            // This gives us the value with $register['width'] bits zeroed.
            $oldValue &= $modeMask ^ $mask;

            // And writes our new value to the register.
            $value += $oldValue;
        }

        $this->registers[$offset] = $value;
    }

    /**
     * Returns a copy of the internal representation of the stack.
     */
    public function getStack(): string
    {
        $this->taintProtection();

        return $this->stack->getStackContents();
    }

    /**
     * Returns the value of the stack at a specified offset.
     *
     * @param int $offset The offset within the stack.
     * @param int $length The amount of bytes to read.
     *
     * @return string The value of the stack at the specified position, as a binary string.
     */
    public function readStackAt(int $offset, int $length): string
    {
        $this->taintProtection();

        return $this->stack->getOffset($offset, $length);
    }

    /**
     * Write to the stack at a specified offset.
     *
     * @param int    $offset The stack offset at which to write the value.
     * @param string $value  The value to write to the stack, as a binary string.
     */
    public function writeStackAt(int $offset, string $value): void
    {
        $this->taintProtection();

        $this->stack->setOffset($offset, $value);

        $sp_offset = Register::SP['offset'];
        $this->registers[$sp_offset] = $offset;
    }

    /**
     * Clears the value on the stack at the specified position. Simply unsets
     * the stack offset.
     *
     * @param int $offset The offset to clear.
     * @param int $length The amount of bytes to clear (between offset and stackAddress).
     */
    public function clearStackAt(int $offset, int $length): void
    {
        $this->taintProtection();

        $this->stack->clearOffset($offset, $length);

        $stackLength = $this->stack->getLength();
        $pointer = $this->stackAddress - $stackLength;

        $sp_offset = Register::SP['offset'];
        $this->registers[$sp_offset] = $pointer;
    }

    /**
     * Returns the currently executing code buffer.
     *
     * @param int $offset Optional. Specifies how many characters to skip from
     *                    the start of the string. See PHP strpos $offset for
     *                    more information.
     * @param int $length Optional. Specifies the string should return at most
     *                    $length number of charactrs. See PHP strpos $length
     *                    for more information.
     */
    public function getCodeBuffer($offset = null, $length = null): string
    {
        if (is_null($offset)) {
            $offset = 0;
        }

        if (is_null($length)) {
            $length = strlen($this->buffer);
        }

        return substr($this->buffer, $offset, $length);
    }

    /**
     * Returns the currently executing code buffer, offset by the instruction
     * pointer position.
     *
     * @see getCodeBuffer()
     *
     * @param int $length Optional. As per getCodeBuffer().
     */
    public function getCodeAtInstruction($length = null): string
    {
        return $this->getCodeBuffer($this->iPointer, $length);
    }

    /**
     * Sets the value of the code buffer within the simulator. NOTE: Calling
     * this function during a simulation would result in undefined behaviour.
     *
     * @param string $buffer The code buffer to be simulated.
     */
    public function setCodeBuffer(string $buffer): void
    {
        $this->buffer = $buffer;
    }

    /**
     * Returns the size of the code buffer in bytes.
     */
    public function getCodeBufferSize(): int
    {
        return strlen($this->buffer);
    }

    /**
     * Returns the current position of the instruction pointer.
     */
    public function getInstructionPointer(): int
    {
        return $this->iPointer;
    }

    /**
     * Sets the absolute value of the instruction pointer.
     *
     * @param int $instructionPointer The new value of the instruction pointer.
     */
    public function setInstructionPointer(int $instructionPointer): void
    {
        $this->iPointer = $instructionPointer;
    }

    /**
     * Advances the instruction pointer, relative to its current position.
     *
     * @param int $amount The amount of bytes to advance the pointer by.
     */
    public function advanceInstructionPointer(int $amount): void
    {
        $this->iPointer += $amount;
    }

    /**
     * Throws an exception if we are trying to operate a tainted environment.
     *
     * @throws Exception\Tainted
     */
    private function taintProtection(): void
    {
        if ($this->tainted) {
            throw new Exception\Tainted(
                'Attempted to operate a tainted environment. ' .
                'Did you forget to reset?',
            );
        }
    }

    /**
     * Processes an opcode with a registered instruction. Any registered
     * instruction may return false to indicate it does not wish to process the
     * opcode, in which instance we will delegate operation to the next opcode
     * until we find an instruction that will process it, or run out of
     * registered instructions for that opcode.
     *
     * @param int $opcode The opcode to process.
     *
     * @return bool Whether or not any instruction processed the opcode.
     */
    private function processOpcodeWithRegisteredInstructions(int $opcode): bool
    {
        // Add our two-byte instruction prefix.
        if ($this->hasPrefix(0x0F)) {
            $opcode = 0xF00 | $opcode;
        }

        foreach ($this->registeredInstructions as $record) {
            // If this Instruction class doesn't handle this opcode, skip it.
            if (! array_key_exists($opcode, $record['mappings'])) {
                continue;
            }

            // This instruction class handles this opcode. Give it a go.
            $callback = $record['mappings'][$opcode];
            $response = call_user_func($callback);

            // If we don't get a boolean response, throw an exception.
            if (! is_bool($response)) {
                $this->triggerInstructionHandlerException($callback, $response);
            }

            // If our mapping returns true, so do we. Otherwise, we continue.
            if ($response) {
                return true;
            }
        }

        // We could not process the opcode, so we return false.
        return false;
    }

    /**
     * Handles generating and throwing the exception for registered instruction
     * processors that do not return a boolean value.
     *
     * @internal
     *
     * @see processOpcodeWithRegisteredInstructions()
     *
     * @param callable $callback The instruction callback that was triggered.
     * @param mixed    $response The invalid response we received.
     *
     * @throws \LogicException
     */
    private function triggerInstructionHandlerException(callable $callback, $response): void
    {
        // Default for string functions. Will most likely be overwritten.
        $instructionName = $callback;

        if (is_array($callback)) {
            $className = $callback[0];

            if (! is_string($className)) {
                $className = get_class($className);
            }

            $instructionName = sprintf(
                '%s::%s',
                $className,
                $callback[1],
            );
        } elseif (is_object($callback)) {
            $instructionName = get_class($callback);
        }

        $message = sprintf(
            'Expected boolean return value from %s, but received "%s" instead.',
            $instructionName,
            var_export($response, true),
        );

        throw new \LogicException($message);
    }

    /**
     * Parses a binary string of assembly to move values around in registers.
     */
    public function simulate(): void
    {
        $this->taintProtection();

        $assembly_length = strlen($this->buffer);

        // Set up our instruction pointer then loop through until the end.
        for (; $this->iPointer < $assembly_length;) {
            $op = ord($this->buffer[$this->iPointer]);

            $isTwoByteOp = $this->hasPrefix(0x0F);

            // Go through our supported instructions.
            switch (true) {
                // 0F: Two-byte Instructions
                case $op == 0x0F && $this->mode !== self::REAL_MODE:

                case $op == 0x66 && $this->mode !== self::REAL_MODE && ! $isTwoByteOp:
                case $op == 0x67 && $this->mode !== self::REAL_MODE && ! $isTwoByteOp:
                    $this->prefixes[] = $op;
                    $this->iPointer++;

                    // If we don't continue to the outer loop, we will clear our
                    // prefix, REX bit and two byte instruction prefix.
                    continue 2;

                case ($op >= 0x40 &&
                      $op <= 0x4f
                      && ! $isTwoByteOp
                      && $this->mode === self::LONG_MODE):

                    $this->prefixes[] = $op;
                    $this->rex = $op;
                    $this->iPointer++;

                    // If we don't continue to the outer loop, we will clear our
                    // prefix, REX bit and two byte instruction prefix.
                    continue 2;

                case $this->processOpcodeWithRegisteredInstructions($op):
                    /**
                     * We have identified that we are passing a registered
                     * instruction. If the instruction processor returns true,
                     * we enter this case and break the switch.
                     */
                    break;

                default:
                    $format =
                        'Encountered unknown opcode 0x%02X at offset %d (0x%X).';
                    $iPointer = $this->iPointer;

                    if ($isTwoByteOp) {
                        $iPointer--;
                        $format = 'Encountered unknown opcode 0x0F%02X at ' .
                                  'offset %d (0x%X).';
                    }

                    $address = $this->addressBase + $iPointer;
                    $message = sprintf($format, $op, $iPointer, $address);

                    throw new Exception\InvalidOpcode($message, $op);
            }

            // Reset our REX bit.
            $this->rex = 0;
            $this->prefixes = [];
        }
    }
}
