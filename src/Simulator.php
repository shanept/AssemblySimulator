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
     * @param int[]
     */
    private $registers = [];

    /**
     * Contains all the values stored in our stack.
     *
     * @param int[]
     */
    private $stack = [];

    /**
     * 32-bit register to store extended flags.
     *
     * @var int
     */
    private $eFlags = 0;

    /**
     * This is our instruction pointer.
     * It may be referred to as IP, EIP or RIP depending on the architecture.
     *
     * @var int
     */
    private $iPointer = 0;

    /**
     * Stores the *real* offset for the address loaded by the LEA operand.
     *
     * @var int
     */
    private $addressBase = 0;

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
     * @param int
     */
    private $mode;

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
    private $rex = 0;

    /**
     * Contains any array of prefixes for the current instruction.
     *
     * @var int[]
     */
    private $prefixes = [];

    /**
     * Stores the currently simulated assembly code.
     *
     * @var string
     */
    private $buffer = false;

    /**
     * Stores whether or not the state has changed since last reset.
     *
     * @var bool
     */
    private $tainted = false;

    /**
     * Stores a list of opcode - callback mappings, thus the consumer may supply
     * their own implementation for an opcode, or overload the existing
     * implementation.
     *
     * @var callable[]
     */
    private $registeredInstructions = [];

    /**
     * @param ?int $mode The machine mode to operate in.
     */
    public function __construct($mode = null)
    {
        $this->mode = $mode ?? self::REAL_MODE;

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
        array $mappings,
    ) {
        $record = compact('reference', 'mappings');
        array_unshift($this->registeredInstructions, $record);
    }

    /**
     * Clears our registers and lets us run again.
     *
     * @return void
     */
    public function reset()
    {
        $this->tainted = false;

        $this->rex = 0;
        $this->prefixes = [];
        $this->buffer = false;


        $this->eFlags = 0;
        $this->stack = [];

        // 16 and 32 bit use 8 registers, 64-bit uses 16.
        $numRegisters = max(8, 2 << $this->mode);
        $this->registers = array_fill(0, $numRegisters, 0);
    }

    /**
     * Returns the mode under which we are operating.
     *
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Returns a user-readable name for the mode under which we are operating.
     *
     * @return string
     */
    public function getModeName()
    {
        return self::MODE_NAMES[$this->mode];
    }

    /**
     * Sets the operating mode. ALWAYS reset the machine after doing this!
     *
     * @param int $mode The machine mode to operate in.
     */
    public function setMode(int $mode)
    {
        $this->tainted = true;

        $this->mode = $mode;
    }

    /**
     * Returns the value for a given flag.
     *
     * @param int $flag The flag to retreive.
     */
    public function getFlag(int $flag): int
    {
        $factor = $flag & -$flag;
        return ($this->eFlags & $flag) / $factor;
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
     * @param int $flag  The flag to be set.
     * @param int $value The value to set the flag to.
     *
     * @return void
     */
    public function setFlag(int $flag, int $value)
    {
        $factor = $flag & -$flag;

        // Remove the existing flag.
        $this->eFlags &= ~$flag;

        // Add the new flag.
        $this->eFlags += $value * $factor;
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
     *
     * @return void
     */
    public function setAddressBase(int $base)
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
     * @param array $register The register reference
     * @param ?int  $size     The parameter width, if required
     *
     * @return int
     */
    public function readRegister(array $register, $size = null): int
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
     * @param array $register The register we are going to write to.
     * @param int   $value    The value to write to be written.
     *
     * @throws LogicException if we are attempting to write to a register too
     *                        large for our simulation mode. (i.e. writing to
     *                        64-bit register in 16-bit REAL mode).
     *
     * @return void
     */
    public function writeRegister(array $register, int $value)
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
     *
     * @return int[]
     */
    public function getStack(): array
    {
        return $this->stack;
    }

    /**
     * Returns the value of the stack at a specified offset.
     *
     * @param int $offset The offset from the start of the stack. (array index)
     */
    public function readStackAt(int $offset): int
    {
        return $this->stack[$offset];
    }

    /**
     * Write to the stack at a specified offset.
     *
     * @param int $offset The stack offset at which to write the value.
     * @param int $value  The value to write to the stack.
     *
     * @return void
     */
    public function writeStackAt(int $offset, int $value)
    {
        $this->stack[$offset] = $value;
    }

    /**
     * Clears the value on the stack at the specified position. Simply unsets
     * the stack offset.
     *
     * @param int $offset The offset to clear.
     *
     * @return void
     */
    public function clearStackAt(int $offset)
    {
        unset($this->stack[$offset]);
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
     *
     * @return void
     */
    public function setCodeBuffer(string $buffer)
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
     *
     * @return void
     */
    public function setInstructionPointer(int $instructionPointer)
    {
        $this->iPointer = $instructionPointer;
    }

    /**
     * Advances the instruction pointer, relative to its current position.
     *
     * @param int $amount The amount of bytes to advance the pointer by.
     *
     * @return void
     */
    public function advanceInstructionPointer(int $amount)
    {
        $this->iPointer += $amount;
    }

    /**
     * Throws an exception if we are trying to operate a tainted environment.
     *
     * @throws shanept\AssemblySimulator\Exception\TaintException
     *
     * @return void
     */
    private function taintProtection()
    {
        $message = 'Attempted to operate a tainted environment. Did you ' .
            'change modes and forget to reset?';

        if ($this->tainted) {
            throw new Exception\TaintException($message);
        }
    }

    /**
     * Determines whether an instruction has been registered to handle an opcode.
     *
     * @param int $opcode The opcode to check for the existance of an instruction.
     */
    private function hasRegisteredInstruction(int $opcode): bool
    {
        // Add our two-byte instruction prefix.
        if ($this->hasPrefix(0x0F)) {
            $opcode = 0xF00 | $opcode;
        }

        foreach ($this->registeredInstructions as $record) {
            if (array_key_exists($opcode, $record['mappings'])) {
                return true;
            }
        }

        return false;
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
     * @return bool Whether or not an instruction processed the opcode.
     */
    private function processOpcodeWithRegisteredInstruction(int $opcode): bool
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
     * @see processOpcodeWithRegisteredInstruction()
     *
     * @throws \LogicException
     */
    private function triggerInstructionHandlerException($callback, $response)
    {
        // Default for string functions. Will most likely be overwritten.
        $instructionName = $callback;

        if (is_array($callback)) {
            $instructionName = sprintf(
                '%s::%s',
                get_class($callback[0]),
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
     *
     * @see http://ref.x86asm.net/coder64.html#gen_note_90_NOP
     *
     * @return void
     */
    public function simulate()
    {
        $this->taintProtection();

        $assembly_length = strlen($this->buffer);
        $this->iPointer = 0;

        // Set up our instruction pointer then loop through until the end.
        for (; $this->iPointer < $assembly_length;) {
            $op = ord($this->buffer[$this->iPointer]);

            $isTwoByteOp = $this->hasPrefix(0x0F);

            // Go through our supported instructions.
            switch (true) {
                // Two-byte Instructions
                case $op == 0x0F && $this->mode !== self::REAL_MODE:
                // Prefixes
                case $op == 0x66 && $this->mode === self::LONG_MODE && ! $isTwoByteOp:
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

                case ($this->hasRegisteredInstruction($op) &&
                      $this->processOpcodeWithRegisteredInstruction($op)):

                    /**
                     * We have identified that we are passing a registered
                     * instruction. If the instruction processor returns true,
                     * we enter this case and break the switch.
                     */
                    break;

                default:
                    $format =
                        'Encountered unknown opcode 0x%x at offset %d (0x%x).';
                    $iPointer = $this->iPointer;

                    if ($isTwoByteOp) {
                        $iPointer--;
                        $format = 'Encountered unknown opcode 0x0F%x at ' .
                                  'offset %d (0x%x).';
                    }

                    $address = $this->addressBase + $iPointer;
                    $message = sprintf($format, $op, $iPointer, $address);

                    throw new \OutOfBoundsException($message, $op);
            }

            // Reset our REX bit.
            $this->rex = 0;
            $this->prefixes = [];
        }
    }
}
