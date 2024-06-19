<?php
/**
 * This defines a factory that will generate a simulator with the default
 * instruction set pre-loaded. Additional instructions may be added during
 * generation of the simulator.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator;

use shanept\AssemblySimulator\Instruction\Call;
use shanept\AssemblySimulator\Instruction\ExclusiveOr;
use shanept\AssemblySimulator\Instruction\LoadEffectiveAddress;
use shanept\AssemblySimulator\Instruction\Move;
use shanept\AssemblySimulator\Instruction\NoOperation;
use shanept\AssemblySimulator\Instruction\Pop;
use shanept\AssemblySimulator\Instruction\Push;

/**
 * A factory to assist with the generation of the simulator.
 *
 * @author Shane Thompson
 *
 * @phpstan-type InstructionList array<int, class-string<Instruction\AssemblyInstruction>>
 */
class SimulatorFactory
{
    /**
     * Factory method to generate a Simulator instance with the default
     * instruction set, plus any additional instructions, if specified.
     *
     * @param int $simulatorMode The mode for the simulator to be instantiated in.
     * @param InstructionList $additionalInstructions A list of additional instruction
     *             FQCN to load into the simulator. These will take processing
     *             priority over the default instruction set.
     */
    public static function createSimulator(
        int $simulatorMode = Simulator::REAL_MODE,
        array $additionalInstructions = [],
    ): Simulator {
        /**
         * We will register additional instructions from lowest to highest
         * priority. This is because the Simulator will reverse the order as
         * we are registering the instructions with it.
         */
        $additionalInstructions = array_reverse($additionalInstructions);

        $instructionSet = self::getDefaultInstructionSet();
        $instructionSet = array_merge($instructionSet, $additionalInstructions);

        $simulator = new Simulator($simulatorMode);

        foreach ($instructionSet as $instructionClass) {
            $instructionInstance = new $instructionClass();
            $instructionInstance->setSimulator($simulator);
        }

        return $simulator;
    }

    /**
     * Returns the default instruction set. This will include all instructions
     * in the Instruction namespace.
     *
     * @return InstructionList
     */
    public static function getDefaultInstructionSet(): array
    {
        return [
            Call::class,
            ExclusiveOr::class,
            LoadEffectiveAddress::class,
            Move::class,
            NoOperation::class,
            Pop::class,
            Push::class,
        ];
    }
}
