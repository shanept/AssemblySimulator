<?php
/**
 * This file provides an example of how one may implement their own instruction
 * to be registered with the simulator.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Instruction\AssemblyInstruction;

/**
 * This is a simple instruction that will push whatever value we desire onto
 * the stack. The binary format of the instruction is as follows, where 0xAB is
 * any 8-bit value that we wish to be pushed onto the stack:
 *
 *      0x01 0xAB
 *
 * All instructions must extend the AssemblyInstruction base class, and define
 * a public function "register".
 */
class myCustomInstruction extends AssemblyInstruction
{
    /**
     * All instructions must implement the "register" method. It must return an
     * array, listing the opcodes it handles as the key, and a callable method
     * that handles the opcode as the value.
     *
     * In our case, we are handling the opcode 0x01, with the "executeOperand1"
     * method. The callable value must follow PHP standards for callables.
     *
     * @return callable[]
     */
    public function register(): array
    {
        return [
            0x01 => [&$this, 'executeOperand1'],
        ];
    }

    /**
     * This defines the instruction processor for operand 0x01. The simulator
     * will call this function whenever it encounters an instruction with opcode
     * 0x01.
     *
     * This function MUST return a boolean value, whether or not it will process
     * this instance of the opcode. This allows a processor to pass on
     * processing if certain criteria have not been met, by returning "false".
     *
     * If an instruction processor passes on processing the operand, the
     * simulator will offer the processing to the next registered instruction
     * that handles this opcode.
     *
     * If an instruction processor returns true, indicating it has processed
     * the opcode, the simulator will not offer any other instruction processors
     * the opportunity to handle this opcode.
     */
    public function executeOperand1(): bool
    {
        $sim = $this->getSimulator();

        /**
         * When we are handed control, the instruction pointer is sitting at the
         * opcode itself. This allows processors to handle multiple opcodes, as
         * required. If this processor only applies to a single opcode, we can
         * immediately advance the instruction pointer to the next position.
         *
         * As an example of how you may implement multiple opcodes for a single
         * operation, you may look at the Move instruction for opcodes B0-BF.
         *
         * Here we advance the instruction pointer by 1 position.
         */
        $sim->advanceInstructionPointer(1);

        /**
         * Now we will read our byte at the instruction pointer to the buffer.
         * The return value is a substring of the code buffer; a binary string.
         * The parameter '1' refers to the amount of bytes to read.
         */
        $byte = $sim->getCodeAtInstruction(1);

        /**
         * For demonstration, this instruction will refuse to process this
         * operation if the byte here is equal to 0x37.
         *
         * When we pass on processing, this opcode may be processed by another
         * processor, and we need to ensure that processor gets the simulator
         * with the same state as it was when this function began.
         *
         * As we previously advanced the instruction pointer 1 byte, we will
         * rewind it before passing on execution.
         */
        if (0x37 === ord($byte)) {
            /**
             * For demonstration purposes, I am going to get and then set the
             * instruction pointer. Alternatively, I could advance the pointer
             * by -1.
             */
            $pointer = $sim->getInstructionPointer();
            $sim->setInstructionPointer($pointer - 1);

            // Now, we pass processing on.
            return false;
        }

        /**
         * Ok, we are going to push this value onto the stack. First, we need
         * to get the stack pointer.
         */
        $stackPointer = $this->getStackPointerRegister();

        // We read the position of the stack from the stack pointer register.
        $stackPosition = $sim->readRegister($stackPointer);

        // The stack pointer always points to the top, so we should decrement
        // before we put onto the stack.
        $stackPosition--;

        // Now we can push our value onto the stack
        $sim->writeStackAt($stackPosition, $byte);

        // Don't forget to decrement the stack pointer!
        $sim->writeRegister($stackPointer, $stackPosition);

        // We have processed the operand, we must let the simulator know!
        return true;
    }
}
